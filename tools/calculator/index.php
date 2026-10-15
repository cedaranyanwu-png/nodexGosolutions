<?php
/**
 * /tools/calculator/index.php
 *
 * Isolated page content component for Calculator module.
 * Designed to render directly inside the dashboard content shell without duplicate HTML document headers.
 */
?>
<div class="card border-0 shadow-sm rounded-xl mb-4">
    <div class="card-header bg-white border-b border-gray-100 py-3 d-flex justify-content-between align-items-center">
        <h3 class="text-base font-bold text-gray-800 m-0 d-flex align-items-center">
            <i class="fa-solid fa-calculator text-primary me-2"></i> Smart Calculator Utility
        </h3>
        <span class="badge bg-primary bg-opacity-10 text-primary text-xs px-2.5 py-1 rounded-full font-semibold">Modular Tool v1.0.0</span>
    </div>
    <div class="card-body p-4">
        <div class="max-w-md mx-auto bg-slate-900 text-white rounded-2xl p-6 shadow-lg border border-slate-800">
            <!-- Calculator Display Output Screen -->
            <div class="mb-4">
                <input
                    type="text"
                    id="calcDisplay"
                    class="w-full bg-slate-950 text-emerald-400 font-mono text-2xl text-right p-4 rounded-xl border border-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-inner"
                    value="0"
                    readonly
                />
            </div>

            <!-- Keypad Matrix Buttons -->
            <div class="grid grid-cols-4 gap-3">
                <button type="button" class="btn btn-secondary fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcClear()">C</button>
                <button type="button" class="btn btn-secondary fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('(')">(</button>
                <button type="button" class="btn btn-secondary fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend(')')">)</button>
                <button type="button" class="btn btn-warning fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('/')">÷</button>

                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('7')">7</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('8')">8</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('9')">9</button>
                <button type="button" class="btn btn-warning fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('*')">×</button>

                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('4')">4</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('5')">5</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('6')">6</button>
                <button type="button" class="btn btn-warning fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('-')">-</button>

                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('1')">1</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('2')">2</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('3')">3</button>
                <button type="button" class="btn btn-warning fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('+')">+</button>

                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90 col-span-2" onclick="calcAppend('0')">0</button>
                <button type="button" class="btn btn-dark fw-bold text-lg py-3 rounded-xl hover:opacity-90" onclick="calcAppend('.')">.</button>
                <button type="button" class="btn btn-primary fw-bold text-lg py-3 rounded-xl hover:opacity-90 bg-blue-600 border-0" onclick="calcCompute()">=</button>
            </div>
        </div>
    </div>
</div>

<script>
    function calcAppend(val) {
        const display = document.getElementById('calcDisplay');
        if (!display) return;
        if (display.value === '0' || display.value === 'Error') {
            display.value = val;
        } else {
            display.value += val;
        }
    }

    function calcClear() {
        const display = document.getElementById('calcDisplay');
        if (display) display.value = '0';
    }

    function calcCompute() {
        const display = document.getElementById('calcDisplay');
        if (!display) return;
        try {
            // Safely evaluate simple math expression using Function constructor
            const expr = display.value.replace(/[^0-9+\-*\/().]/g, '');
            const result = new Function('return ' + expr)();
            display.value = result;
        } catch (e) {
            display.value = 'Error';
        }
    }
</script>
