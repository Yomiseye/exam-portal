

import Alpine from 'alpinejs';

const bindExamCalculator = () => {
    const toggle = document.querySelector('[data-calculator-toggle]');
    const shell = document.querySelector('[data-exam-calculator]');
    const display = document.querySelector('[data-calculator-display]');
    const expressionPreview = document.querySelector('[data-calculator-expression]');

    if (! toggle || ! shell || ! display || shell.dataset.bound === 'true') {
        return;
    }

    shell.dataset.bound = 'true';

    let expression = display.textContent.trim() || '0';
    let hasError = false;
    const operators = ['+', '-', '*', '/', '%'];

    const syncAlpine = () => {
        if (! window.Alpine) {
            return;
        }

        try {
            const component = window.Alpine.$data(toggle.closest('[x-data]'));
            component.calculatorOpen = true;
            component.calculatorDisplay = expression;
            component.calculatorError = hasError;
        } catch (error) {
            // The visible sidebar calculator is the source of truth.
        }
    };

    const render = () => {
        display.textContent = expression;

        if (expressionPreview) {
            expressionPreview.textContent = hasError
                ? 'Invalid expression'
                : (expression === '0' ? 'Ready' : expression);
        }

        syncAlpine();
    };

    const open = () => {
        shell.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        syncAlpine();
    };

    const close = () => {
        open();
    };

    const clear = () => {
        expression = '0';
        hasError = false;
        render();
    };

    const backspace = () => {
        if (hasError || expression.length <= 1) {
            clear();
            return;
        }

        expression = expression.slice(0, -1);
        render();
    };

    const append = (value) => {
        if (hasError) {
            clear();
        }

        const last = expression.slice(-1);

        if (operators.includes(value) && operators.includes(last)) {
            expression = expression.slice(0, -1) + value;
            render();
            return;
        }

        if (value === '.' && expression.split(/[+\-*/%()]/).pop().includes('.')) {
            return;
        }

        if (expression === '0' && ! operators.includes(value) && value !== '.') {
            expression = value;
            render();
            return;
        }

        if (expression.length < 42) {
            expression += value;
            render();
        }
    };

    const appendParenthesis = () => {
        if (hasError) {
            clear();
        }

        const openCount = (expression.match(/\(/g) || []).length;
        const closeCount = (expression.match(/\)/g) || []).length;
        const last = expression.slice(-1);
        const value = openCount > closeCount && ! operators.includes(last) && last !== '(' ? ')' : '(';

        append(value);
    };

    const tokenize = (value) => {
        const tokens = [];
        let number = '';

        for (const char of value.replace(/\s+/g, '')) {
            if (/[0-9.]/.test(char)) {
                number += char;
                continue;
            }

            if (number !== '') {
                tokens.push(number);
                number = '';
            }

            if ('+-*/%()'.includes(char)) {
                tokens.push(char);
            } else {
                throw new Error('Invalid character');
            }
        }

        if (number !== '') {
            tokens.push(number);
        }

        return tokens;
    };

    const evaluateExpression = (value) => {
        const precedence = { u: 3, '*': 2, '/': 2, '%': 2, '+': 1, '-': 1 };
        const output = [];
        const stack = [];
        let previous = null;

        for (const token of tokenize(value)) {
            if (! Number.isNaN(Number(token))) {
                output.push(Number(token));
                previous = 'number';
                continue;
            }

            if (token === '(') {
                stack.push(token);
                previous = '(';
                continue;
            }

            if (token === ')') {
                while (stack.length && stack[stack.length - 1] !== '(') {
                    output.push(stack.pop());
                }

                if (stack.pop() !== '(') {
                    throw new Error('Mismatched parentheses');
                }

                previous = 'number';
                continue;
            }

            const operator = token === '-' && (previous === null || previous === '(' || previous === 'operator') ? 'u' : token;

            while (
                stack.length
                && stack[stack.length - 1] !== '('
                && precedence[stack[stack.length - 1]] >= precedence[operator]
            ) {
                output.push(stack.pop());
            }

            stack.push(operator);
            previous = 'operator';
        }

        while (stack.length) {
            const operator = stack.pop();

            if (operator === '(') {
                throw new Error('Mismatched parentheses');
            }

            output.push(operator);
        }

        const values = [];

        for (const token of output) {
            if (typeof token === 'number') {
                values.push(token);
                continue;
            }

            if (token === 'u') {
                values.push(-values.pop());
                continue;
            }

            const right = values.pop();
            const left = values.pop();

            if (left === undefined || right === undefined) {
                throw new Error('Invalid expression');
            }

            if (token === '+') values.push(left + right);
            if (token === '-') values.push(left - right);
            if (token === '*') values.push(left * right);
            if (token === '/') values.push(left / right);
            if (token === '%') values.push(left % right);
        }

        if (values.length !== 1 || ! Number.isFinite(values[0])) {
            throw new Error('Invalid result');
        }

        return values[0];
    };

    const calculate = () => {
        if (! /^[0-9+\-*/%.() ]+$/.test(expression)) {
            expression = 'Error';
            hasError = true;
            render();
            return;
        }

        try {
            const result = evaluateExpression(expression);
            expression = Number.isInteger(result) ? String(result) : String(Number(result.toFixed(8)));
            hasError = false;
        } catch (error) {
            expression = 'Error';
            hasError = true;
        }

        render();
    };

    toggle.addEventListener('click', (event) => {
        open();
    });

    shell.addEventListener('click', (event) => {
        const button = event.target.closest('button');

        if (! button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (button.matches('[data-calculator-close]')) {
            close();
            return;
        }

        if (button.dataset.calculatorValue !== undefined) {
            append(button.dataset.calculatorValue);
            return;
        }

        if (button.dataset.calculatorAction === 'clear') clear();
        if (button.dataset.calculatorAction === 'backspace') backspace();
        if (button.dataset.calculatorAction === 'parentheses') appendParenthesis();
        if (button.dataset.calculatorAction === 'equals') calculate();
    }, true);

    document.addEventListener('keydown', (event) => {
        const keyMap = {
            Enter: '=',
            Escape: null,
            Backspace: 'backspace',
            Delete: 'clear',
            x: '*',
            X: '*',
        };
        const key = keyMap[event.key] ?? event.key;
        const allowed = '0123456789.+-*/%()'.split('');

        if (! allowed.includes(key) && ! ['=', 'backspace', 'clear'].includes(key)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (allowed.includes(key)) append(key);
        if (key === '=') calculate();
        if (key === 'backspace') backspace();
        if (key === 'clear') clear();
    });

    render();
};

window.Alpine = Alpine;

Alpine.start();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindExamCalculator);
} else {
    bindExamCalculator();
}
