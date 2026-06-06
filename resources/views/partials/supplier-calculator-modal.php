<div class="modal-overlay" id="supplierCalculatorModal" hidden aria-hidden="true">
    <div class="modal-panel modal-panel-calculator" role="dialog" aria-labelledby="calcModalTitle" aria-modal="true">
        <div class="modal-header">
            <h2 class="modal-title" id="calcModalTitle">Calculator</h2>
            <button type="button" class="modal-close" data-modal-close="supplierCalculatorModal" aria-label="Close calculator">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calc-display">
                <div class="calc-expression" id="calcExpression"></div>
                <div class="calc-result" id="calcResult">0</div>
            </div>
            <div class="calc-keypad">
                <button type="button" class="calc-key calc-key-fn" data-calc-action="clear">C</button>
                <button type="button" class="calc-key calc-key-fn" data-calc-action="clear-entry">CE</button>
                <button type="button" class="calc-key calc-key-fn" data-calc-action="backspace">⌫</button>
                <button type="button" class="calc-key calc-key-op" data-calc-action="op" data-calc-value="/">÷</button>

                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="7">7</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="8">8</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="9">9</button>
                <button type="button" class="calc-key calc-key-op" data-calc-action="op" data-calc-value="*">×</button>

                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="4">4</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="5">5</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="6">6</button>
                <button type="button" class="calc-key calc-key-op" data-calc-action="op" data-calc-value="-">−</button>

                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="1">1</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="2">2</button>
                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="3">3</button>
                <button type="button" class="calc-key calc-key-op" data-calc-action="op" data-calc-value="+">+</button>

                <button type="button" class="calc-key" data-calc-action="digit" data-calc-value="0">0</button>
                <button type="button" class="calc-key" data-calc-action="decimal">.</button>
                <button type="button" class="calc-key calc-key-fn" data-calc-action="percent">%</button>
                <button type="button" class="calc-key calc-key-eq" data-calc-action="equals">=</button>
            </div>
            <p class="modal-hint">Standalone tool — no ERP accounting impact.</p>
        </div>
    </div>
</div>
