<?php

require_once DIR_SYSTEM . 'library/ibs/api_settings.php';

/**
 * Read-only order queries for IBS Sync Connector (supplier queue + warehouse lines only).
 */
class ModelApiIbsOrder extends Model
{
    private $orderColumns = null;
    private $settings = null;

    public function getPagedOrders(int $page, int $limit, array $filters = []): array
    {
        $settings = $this->settings();
        $queueIds = $settings['queue_status_ids'] ?? [];
        $bridgeTable = trim((string) ($settings['bridge_table'] ?? 'dispatch_location_product'));
        if ($bridgeTable === '') {
            $bridgeTable = 'dispatch_location_product';
        }

        if ($queueIds === []) {
            return [
                'orders' => [],
                'total' => 0,
                'filter_applied' => 'queue_empty',
                'queue_status_ids' => [],
                'warning' => 'No supplier queue statuses configured in connector admin.',
            ];
        }

        $languageId = (int) $this->config->get('config_language_id');
        $offset = ($page - 1) * $limit;
        $bridge = DB_PREFIX . $bridgeTable;
        $queueIdList = implode(',', array_map('intval', $queueIds));

        $where = ['o.order_status_id IN (' . $queueIdList . ')'];
        $where[] = 'EXISTS ('
            . 'SELECT 1 FROM `' . DB_PREFIX . 'order_product` op '
            . 'INNER JOIN `' . $bridge . '` dlp ON dlp.product_id = op.product_id AND dlp.from_warehouse = 1 '
            . 'WHERE op.order_id = o.order_id'
            . ')';

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'DATE(o.date_added) >= \'' . $this->db->escape($dateFrom) . '\'';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'DATE(o.date_added) <= \'' . $this->db->escape($dateTo) . '\'';
        }

        $whereSql = implode(' AND ', $where);

        $totalQuery = $this->db->query(
            'SELECT COUNT(*) AS total FROM `' . DB_PREFIX . 'order` o WHERE ' . $whereSql
        );
        $total = (int) ($totalQuery->row['total'] ?? 0);

        $extraSelect = $this->buildExtraSelect('o');
        $query = $this->db->query(
            'SELECT o.order_id, o.invoice_no, o.firstname, o.lastname, o.telephone, o.email, '
            . 'o.order_status_id, o.total, o.date_added, o.shipping_address_1, o.shipping_address_2, '
            . 'o.shipping_city, o.payment_method, o.shipping_method, os.name AS order_status'
            . $extraSelect
            . ' FROM `' . DB_PREFIX . 'order` o '
            . 'LEFT JOIN `' . DB_PREFIX . 'order_status` os ON o.order_status_id = os.order_status_id AND os.language_id = ' . $languageId . ' '
            . 'WHERE ' . $whereSql . ' '
            . 'ORDER BY o.order_id DESC '
            . 'LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset
        );

        $orders = [];
        $orderIds = [];

        foreach ($query->rows as $row) {
            $orderId = (int) ($row['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $orderIds[] = $orderId;
            $orders[$orderId] = $this->mapOrderRow($row);
        }

        if ($orderIds !== []) {
            $productsByOrder = $this->getWarehouseProductsForOrders($orderIds, $bridgeTable);
            foreach ($productsByOrder as $orderId => $products) {
                if (!isset($orders[$orderId])) {
                    continue;
                }
                if ($products === []) {
                    unset($orders[$orderId]);
                    continue;
                }

                $orders[$orderId]['products'] = $products;
                $orders[$orderId]['total_quantity'] = array_sum(array_map(function (array $item) {
                    return (int) ($item['quantity'] ?? 0);
                }, $products));
                $orders[$orderId]['has_warehouse_product'] = true;
            }

            foreach ($orderIds as $orderId) {
                if (isset($orders[$orderId]) && ($orders[$orderId]['products'] ?? []) === []) {
                    unset($orders[$orderId]);
                }
            }
        }

        return [
            'orders' => array_values($orders),
            'total' => $total,
            'filter_applied' => 'queue_and_warehouse',
            'queue_status_ids' => array_values(array_map('strval', $queueIds)),
        ];
    }

    private function mapOrderRow(array $row): array
    {
        $map = $this->settings()['order_field_map'] ?? [];
        $firstName = trim((string) ($row['firstname'] ?? ''));
        $lastName = trim((string) ($row['lastname'] ?? ''));
        $customerName = trim($firstName . ' ' . $lastName);
        $addressParts = array_filter([
            trim((string) ($row['shipping_address_1'] ?? '')),
            trim((string) ($row['shipping_address_2'] ?? '')),
            trim((string) ($row['shipping_city'] ?? '')),
        ]);
        $orderStatusId = (string) ($row['order_status_id'] ?? '');
        $orderStatusName = (string) ($row['order_status'] ?? '');

        return [
            'order_id' => (string) ($row['order_id'] ?? ''),
            'invoice_no' => (string) ($row['invoice_no'] ?? ''),
            'firstname' => $firstName,
            'lastname' => $lastName,
            'customer_name' => $customerName,
            'telephone' => (string) ($row['telephone'] ?? ''),
            'customer_phone' => (string) ($row['telephone'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'order_status_id' => $orderStatusId,
            'order_status' => $orderStatusName,
            'connector_queue_status' => $orderStatusId,
            'connector_queue_label' => $orderStatusName,
            'in_supplier_queue' => true,
            'has_warehouse_product' => false,
            'total' => round((float) ($row['total'] ?? 0), 2),
            'order_total' => round((float) ($row['total'] ?? 0), 2),
            'date_added' => (string) ($row['date_added'] ?? ''),
            'shipping_address_1' => (string) ($row['shipping_address_1'] ?? ''),
            'shipping_address_2' => (string) ($row['shipping_address_2'] ?? ''),
            'shipping_city' => (string) ($row['shipping_city'] ?? ''),
            'customer_address' => implode(', ', $addressParts),
            'payment_method' => (string) ($row['payment_method'] ?? ''),
            'shipping_method' => (string) ($row['shipping_method'] ?? ''),
            'courier_status' => $this->resolveMappedField($row, $map['courier_status'] ?? ['courier_status', 'shipping_status']),
            'consignment_id' => $this->resolveMappedField($row, $map['consignment_id'] ?? ['consignment_id', 'tracking_number', 'tracking_no']),
            'courier_name' => $this->resolveMappedField($row, $map['courier_name'] ?? ['courier_name', 'shipping_method']),
            'products' => [],
            'total_quantity' => 0,
        ];
    }

    private function resolveMappedField(array $row, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            if (array_key_exists($candidate, $row) && (string) $row[$candidate] !== '') {
                return (string) $row[$candidate];
            }
        }

        return '';
    }

    /**
     * Line-level filter: only order_product rows linked to dispatch bridge from_warehouse = 1.
     *
     * @param array<int, int> $orderIds
     */
    private function getWarehouseProductsForOrders(array $orderIds, string $bridgeTable): array
    {
        $ids = array_values(array_filter(array_map('intval', $orderIds), function ($id) {
            return (int) $id > 0;
        }));
        if ($ids === []) {
            return [];
        }

        $bridge = DB_PREFIX . $bridgeTable;
        $idList = implode(',', $ids);
        $query = $this->db->query(
            'SELECT op.order_product_id, op.order_id, op.product_id, op.name, op.model, op.quantity, op.price, op.total, op.tax, '
            . 'dlp.from_warehouse '
            . 'FROM `' . DB_PREFIX . 'order_product` op '
            . 'INNER JOIN `' . $bridge . '` dlp ON dlp.product_id = op.product_id AND dlp.from_warehouse = 1 '
            . 'WHERE op.order_id IN (' . $idList . ') '
            . 'ORDER BY op.order_id ASC, op.order_product_id ASC'
        );

        $optionsByLine = $this->getOrderOptionsForOrders($ids);
        $grouped = [];

        foreach ($query->rows as $row) {
            $orderId = (int) ($row['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $lineId = (int) ($row['order_product_id'] ?? 0);
            $optionLabel = $optionsByLine[$lineId] ?? null;

            $grouped[$orderId][] = [
                'product_id' => isset($row['product_id']) ? (string) $row['product_id'] : '',
                'name' => (string) ($row['name'] ?? ''),
                'model' => (string) ($row['model'] ?? ''),
                'quantity' => (int) ($row['quantity'] ?? 0),
                'price' => round((float) ($row['price'] ?? 0), 2),
                'total' => round((float) ($row['total'] ?? 0), 2),
                'sku' => (string) ($row['model'] ?? ''),
                'option' => $optionLabel,
                'from_warehouse' => 1,
            ];
        }

        return $grouped;
    }

    private function getOrderOptionsForOrders(array $orderIds): array
    {
        $idList = implode(',', array_map('intval', $orderIds));
        $query = $this->db->query(
            'SELECT order_product_id, name, value FROM `' . DB_PREFIX . 'order_option` '
            . 'WHERE order_id IN (' . $idList . ') '
            . 'ORDER BY order_product_id ASC, order_option_id ASC'
        );

        $labels = [];
        foreach ($query->rows as $row) {
            $lineId = (int) ($row['order_product_id'] ?? 0);
            if ($lineId <= 0) {
                continue;
            }

            $part = trim((string) ($row['name'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($part === '') {
                continue;
            }
            if ($value !== '') {
                $part .= ': ' . $value;
            }

            if (!isset($labels[$lineId])) {
                $labels[$lineId] = $part;
            } else {
                $labels[$lineId] .= ', ' . $part;
            }
        }

        return $labels;
    }

    private function buildExtraSelect(string $alias): string
    {
        $columns = $this->resolveOrderColumns();
        $parts = [];
        foreach ($columns as $column) {
            $parts[] = ', ' . $alias . '.' . $column;
        }

        return implode('', $parts);
    }

    private function resolveOrderColumns(): array
    {
        if ($this->orderColumns !== null) {
            return $this->orderColumns;
        }

        $map = $this->settings()['order_field_map'] ?? [];
        $candidates = [];
        foreach (['courier_status', 'consignment_id', 'courier_name'] as $key) {
            foreach ((array) ($map[$key] ?? []) as $column) {
                $column = trim((string) $column);
                if ($column !== '') {
                    $candidates[] = $column;
                }
            }
        }

        $candidates = array_values(array_unique($candidates));
        $existing = [];
        foreach ($candidates as $column) {
            if ($this->columnExists('order', $column)) {
                $existing[] = $column;
            }
        }

        $this->orderColumns = $existing;

        return $this->orderColumns;
    }

    private function columnExists(string $tableSuffix, string $column): bool
    {
        $query = $this->db->query(
            'SHOW COLUMNS FROM `' . DB_PREFIX . $tableSuffix . '` LIKE \'' . $this->db->escape($column) . '\''
        );

        return $query->num_rows > 0;
    }

    private function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $loader = new \ibs\api_settings($this->registry);
        $this->settings = $loader->all();

        return $this->settings;
    }
}
