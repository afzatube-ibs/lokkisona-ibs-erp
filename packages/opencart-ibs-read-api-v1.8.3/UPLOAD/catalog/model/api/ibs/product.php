<?php

/**
 * Read-only product queries for IBS API (v1.8.3).
 * SELECT only — Dispatch Location bridge (from_warehouse = 1).
 */
class ModelApiIbsProduct extends Model
{
    private $bridgeAvailable = null;
    private $optionImageTable = null;
    private $optionModelColumn = null;
    private $tableExistsCache = [];

    public function bridgeAvailable(string $bridgeTable): bool
    {
        if ($this->bridgeAvailable !== null) {
            return $this->bridgeAvailable;
        }

        $this->bridgeAvailable = $this->tableExists($bridgeTable)
            && $this->columnExists($bridgeTable, 'product_id')
            && $this->columnExists($bridgeTable, 'from_warehouse');

        return $this->bridgeAvailable;
    }

    public function getPagedProducts(string $bridgeTable, int $page, int $limit): array
    {
        if (!$this->bridgeAvailable($bridgeTable)) {
            return [
                'products' => [],
                'total' => 0,
            ];
        }

        $languageId = (int) $this->config->get('config_language_id');
        $offset = ($page - 1) * $limit;
        $bridge = DB_PREFIX . $bridgeTable;

        $totalQuery = $this->db->query(
            'SELECT COUNT(DISTINCT p.product_id) AS total '
            . 'FROM `' . DB_PREFIX . "product` p "
            . 'INNER JOIN `' . $bridge . '` dlp ON dlp.product_id = p.product_id AND dlp.from_warehouse = 1'
        );
        $total = (int) ($totalQuery->row['total'] ?? 0);

        $productQuery = $this->db->query(
            'SELECT DISTINCT p.product_id, pd.name, p.model, p.image, p.price, p.quantity, p.status, dlp.from_warehouse '
            . 'FROM `' . DB_PREFIX . "product` p "
            . 'INNER JOIN `' . DB_PREFIX . 'product_description` pd ON p.product_id = pd.product_id AND pd.language_id = ' . $languageId . ' '
            . 'INNER JOIN `' . $bridge . '` dlp ON dlp.product_id = p.product_id AND dlp.from_warehouse = 1 '
            . 'ORDER BY p.product_id ASC '
            . 'LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset
        );

        $products = [];
        $productIds = [];

        foreach ($productQuery->rows as $row) {
            $productId = (int) $row['product_id'];
            $productIds[] = $productId;
            $products[$productId] = [
                'product_id' => (string) $productId,
                'name' => (string) ($row['name'] ?? ''),
                'model' => (string) ($row['model'] ?? ''),
                'image' => (string) ($row['image'] ?? ''),
                'price' => round((float) ($row['price'] ?? 0), 2),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'status' => (int) ($row['status'] ?? 0),
                'from_warehouse' => (int) ($row['from_warehouse'] ?? 1),
                'options' => [],
            ];
        }

        if ($productIds !== []) {
            $optionsByProduct = $this->getOptionsForProducts($productIds, $languageId);
            foreach ($optionsByProduct as $productId => $options) {
                if (isset($products[$productId])) {
                    $products[$productId]['options'] = $options;
                }
            }
        }

        return [
            'products' => array_values($products),
            'total' => $total,
        ];
    }

    private function getOptionsForProducts(array $productIds, int $languageId): array
    {
        $ids = array_map('intval', $productIds);
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return [];
        }

        $idList = implode(',', $ids);
        $modelSelect = $this->resolveOptionModelSelect('pov');
        $imageJoin = $this->resolveOptionImageJoin('pov');

        $sql = 'SELECT po.product_id, po.product_option_id, pov.product_option_value_id, po.required, '
            . 'od.name AS option_name, ovd.name AS option_value, pov.quantity, pov.subtract, pov.price, pov.price_prefix'
            . $modelSelect . $imageJoin['select']
            . ' FROM `' . DB_PREFIX . 'product_option` po '
            . 'INNER JOIN `' . DB_PREFIX . 'product_option_value` pov ON po.product_option_id = pov.product_option_id '
            . 'INNER JOIN `' . DB_PREFIX . 'option_description` od ON po.option_id = od.option_id AND od.language_id = ' . $languageId . ' '
            . 'INNER JOIN `' . DB_PREFIX . 'option_value_description` ovd ON pov.option_value_id = ovd.option_value_id AND ovd.language_id = ' . $languageId . ' '
            . $imageJoin['join']
            . ' WHERE po.product_id IN (' . $idList . ') '
            . 'ORDER BY po.product_id ASC, po.product_option_id ASC, pov.product_option_value_id ASC';

        $query = $this->db->query($sql);
        $grouped = [];

        foreach ($query->rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $option = [
                'product_option_id' => (string) ($row['product_option_id'] ?? ''),
                'product_option_value_id' => (string) ($row['product_option_value_id'] ?? ''),
                'option_name' => (string) ($row['option_name'] ?? ''),
                'option_value' => (string) ($row['option_value'] ?? ''),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'subtract' => (int) ($row['subtract'] ?? 0),
                'price' => round((float) ($row['price'] ?? 0), 2),
                'price_prefix' => (string) ($row['price_prefix'] ?? '+'),
                'required' => (int) ($row['required'] ?? 0),
            ];

            if (array_key_exists('option_model', $row)) {
                $option['model'] = (string) ($row['option_model'] ?? '');
            }

            if (array_key_exists('option_image', $row)) {
                $option['image'] = (string) ($row['option_image'] ?? '');
            }

            $grouped[$productId][] = $option;
        }

        return $grouped;
    }

    /**
     * Read-only probe for POIP / option image tables (connection test + diagnostics).
     *
     * @param array<int, int|string> $sampleValueIds
     */
    public function probeOptionImageSources(array $sampleValueIds = []): array
    {
        $detected = [];
        $candidateTables = [
            'product_option_value',
            'poip_product_option_value',
            'product_option_value_image',
            'improved_option_value',
        ];

        foreach ($candidateTables as $tableSuffix) {
            if (!$this->tableExists($tableSuffix)) {
                continue;
            }

            $imageColumns = [];
            foreach (['image', 'optionimage', 'option_image', 'thumb'] as $column) {
                if ($this->columnExists($tableSuffix, $column)) {
                    $imageColumns[] = $column;
                }
            }

            $detected[] = [
                'table' => DB_PREFIX . $tableSuffix,
                'image_columns' => $imageColumns,
                'has_product_option_value_id' => $this->columnExists($tableSuffix, 'product_option_value_id'),
            ];
        }

        $samples = [];
        $ids = [];
        foreach ($sampleValueIds as $valueId) {
            $valueId = (int) $valueId;
            if ($valueId > 0) {
                $ids[$valueId] = $valueId;
            }
        }
        if ($ids === []) {
            $ids = [971, 972, 1011, 1024];
        }

        $imageJoin = $this->resolveOptionImageJoin('pov');
        if (($imageJoin['select'] ?? '') !== '') {
            $idList = implode(',', array_map('intval', array_values($ids)));
            $sql = 'SELECT pov.product_option_value_id' . $imageJoin['select']
                . ' FROM `' . DB_PREFIX . 'product_option_value` pov '
                . $imageJoin['join']
                . ' WHERE pov.product_option_value_id IN (' . $idList . ')';
            $query = $this->db->query($sql);
            foreach ($query->rows as $row) {
                $valueId = (int) ($row['product_option_value_id'] ?? 0);
                if ($valueId <= 0) {
                    continue;
                }
                $samples[(string) $valueId] = trim((string) ($row['option_image'] ?? ''));
            }
        }

        $nonEmptySamples = array_filter($samples, static fn (string $path): bool => $path !== '');

        return [
            'join_active' => ($imageJoin['select'] ?? '') !== '',
            'detected_tables' => $detected,
            'sample_value_ids' => array_values($ids),
            'sample_images' => $samples,
            'sample_images_non_empty' => count($nonEmptySamples),
        ];
    }

    private function resolveOptionModelSelect(string $alias): string
    {
        if ($this->optionModelColumn === null) {
            $this->optionModelColumn = $this->columnExists('product_option_value', 'model') ? 'model' : '';
        }

        if ($this->optionModelColumn === '') {
            return '';
        }

        return ', ' . $alias . '.' . $this->optionModelColumn . ' AS option_model';
    }

    private function resolveOptionImageJoin(string $alias): array
    {
        if ($this->optionImageTable !== null) {
            return $this->optionImageTable;
        }

        $selectParts = [];
        $joins = '';

        foreach (['optionimage', 'image', 'option_image'] as $column) {
            if ($this->columnExists('product_option_value', $column)) {
                $selectParts[] = 'NULLIF(' . $alias . '.' . $column . ', \'\')';
                break;
            }
        }

        $joinIndex = 0;
        foreach (['poip_product_option_value', 'product_option_value_image', 'improved_option_value'] as $candidate) {
            if (!$this->tableExists($candidate) || !$this->columnExists($candidate, 'product_option_value_id')) {
                continue;
            }

            $imageColumn = '';
            foreach (['image', 'optionimage', 'option_image'] as $column) {
                if ($this->columnExists($candidate, $column)) {
                    $imageColumn = $column;
                    break;
                }
            }
            if ($imageColumn === '') {
                continue;
            }

            $joinAlias = 'oimg' . $joinIndex;
            $joinIndex++;
            $joins .= ' LEFT JOIN `' . DB_PREFIX . $candidate . '` ' . $joinAlias
                . ' ON ' . $joinAlias . '.product_option_value_id = ' . $alias . '.product_option_value_id ';
            $selectParts[] = 'NULLIF(' . $joinAlias . '.' . $imageColumn . ', \'\')';
        }

        if ($selectParts === []) {
            $this->optionImageTable = ['select' => '', 'join' => ''];

            return $this->optionImageTable;
        }

        $this->optionImageTable = [
            'select' => ', COALESCE(' . implode(', ', $selectParts) . ') AS option_image',
            'join' => $joins,
        ];

        return $this->optionImageTable;
    }

    private function tableExists(string $tableSuffix): bool
    {
        $key = 't:' . $tableSuffix;
        if (array_key_exists($key, $this->tableExistsCache)) {
            return $this->tableExistsCache[$key];
        }

        $query = $this->db->query(
            'SHOW TABLES LIKE \'' . $this->db->escape(DB_PREFIX . $tableSuffix) . '\''
        );
        $this->tableExistsCache[$key] = $query->num_rows > 0;

        return $this->tableExistsCache[$key];
    }

    private function columnExists(string $tableSuffix, string $column): bool
    {
        $key = 'c:' . $tableSuffix . ':' . $column;
        if (array_key_exists($key, $this->tableExistsCache)) {
            return $this->tableExistsCache[$key];
        }

        if (!$this->tableExists($tableSuffix)) {
            $this->tableExistsCache[$key] = false;

            return false;
        }

        $query = $this->db->query(
            'SHOW COLUMNS FROM `' . DB_PREFIX . $tableSuffix . '` LIKE \'' . $this->db->escape($column) . '\''
        );
        $this->tableExistsCache[$key] = $query->num_rows > 0;

        return $this->tableExistsCache[$key];
    }
}
