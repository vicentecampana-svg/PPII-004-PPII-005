/**
 * Validación en el navegador de la política de contraseñas.
 *
 * Las reglas y mensajes vienen del servidor (PasswordPolicy::clientConfig())
 * en el atributo data-password-policy del <form>, así que son las mismas que
 * aplica PHP; el servidor sigue validando como respaldo.
 *
 * Marcado esperado dentro del formulario:
 *   - data-password-new         campo donde se define la contraseña. Se valida
 *                               al perder el foco y, desde entonces, al escribir.
 *   - data-password-optional    (opcional) un campo vacío se considera válido.
 *   - data-password-confirm="id" campo de confirmación; se compara en tiempo
 *                               real con el campo de ese id.
 *   - data-error-id="id"        elemento donde se muestra el error del campo.
 *
 * Si al enviar hay errores, se bloquea el envío para no recargar la página y
 * perder lo escrito.
 */
(function () {
  'use strict';

  function parseConfig(form) {
    try {
      var config = JSON.parse(form.getAttribute('data-password-policy') || '{}');
      config.rules = (config.rules || []).map(function (rule) {
        return { regex: new RegExp(rule.pattern, 'u'), message: rule.message };
      });
      return config;
    } catch (e) {
      return null;
    }
  }

  function policyErrors(config, value) {
    var errors = [];
    // Array.from cuenta puntos de código, igual que mb_strlen() en PHP.
    var length = Array.from(value).length;

    if (length < config.minLength) {
      errors.push(config.minLengthMessage);
    }
    if (length > config.maxLength) {
      errors.push(config.maxLengthMessage);
    }
    config.rules.forEach(function (rule) {
      if (!rule.regex.test(value)) {
        errors.push(rule.message);
      }
    });

    return errors;
  }

  function errorElementFor(input) {
    var id = input.getAttribute('data-error-id') || (input.id + '-error');
    var el = document.getElementById(id);

    if (!el) {
      el = document.createElement('p');
      el.id = id;
      el.className = 'field-error';
      input.insertAdjacentElement('afterend', el);
    }
    el.setAttribute('aria-live', 'polite');

    return el;
  }

  function showErrors(input, messages) {
    var el = errorElementFor(input);
    el.textContent = '';

    if (messages.length === 0) {
      el.hidden = true;
      input.removeAttribute('aria-invalid');
      input.removeAttribute('aria-describedby');
      return;
    }

    messages.forEach(function (message) {
      var line = document.createElement('span');
      line.className = 'field-error-line';
      line.textContent = message;
      el.appendChild(line);
    });
    el.hidden = false;
    input.setAttribute('aria-invalid', 'true');
    input.setAttribute('aria-describedby', el.id);
  }

  function setup(form) {
    var config = parseConfig(form);
    if (!config) {
      return;
    }

    var newInputs = Array.prototype.slice.call(form.querySelectorAll('[data-password-new]'));
    var confirmInputs = Array.prototype.slice.call(form.querySelectorAll('[data-password-confirm]'));

    function validateNew(input) {
      var value = input.value;
      input.setAttribute('data-password-checked', '');
      var messages = (value === '' && input.hasAttribute('data-password-optional'))
        ? []
        : policyErrors(config, value);
      showErrors(input, messages);

      return messages.length === 0;
    }

    function validateConfirm(input, force) {
      var target = document.getElementById(input.getAttribute('data-password-confirm'));
      var messages = [];

      // Sin forzar (mientras se escribe), un campo vacío no muestra error.
      if (target && (force || input.value !== '') && input.value !== target.value) {
        messages.push(config.mismatchMessage);
      }
      showErrors(input, messages);

      return messages.length === 0;
    }

    newInputs.forEach(function (input) {
      // Hasta que el campo se valida por primera vez (al perder el foco o al
      // intentar enviar) no se muestran errores; desde ahí se revalida al
      // escribir para que el aviso desaparezca apenas la contraseña cumple.
      input.addEventListener('blur', function (event) {
        if (input.value === '' && !input.hasAttribute('data-password-checked')) {
          return;
        }
        // Si el foco pasa al botón de envío, valida el submit: mostrar el error
        // aquí desplazaría el botón y el clic se perdería.
        var next = event.relatedTarget;
        if (next && next.type === 'submit' && form.contains(next)) {
          return;
        }
        validateNew(input);
      });

      input.addEventListener('input', function () {
        if (input.hasAttribute('data-password-checked')) {
          validateNew(input);
        }
        confirmInputs.forEach(function (confirm) {
          if (confirm.getAttribute('data-password-confirm') === input.id && confirm.value !== '') {
            validateConfirm(confirm, false);
          }
        });
      });
    });

    confirmInputs.forEach(function (input) {
      input.addEventListener('input', function () {
        validateConfirm(input, false);
      });
    });

    form.addEventListener('submit', function (event) {
      var firstInvalid = null;

      newInputs.forEach(function (input) {
        if (!validateNew(input) && !firstInvalid) {
          firstInvalid = input;
        }
      });
      confirmInputs.forEach(function (input) {
        if (!validateConfirm(input, true) && !firstInvalid) {
          firstInvalid = input;
        }
      });

      if (firstInvalid) {
        event.preventDefault();
        firstInvalid.focus();
      }
    });
  }

  document.querySelectorAll('form[data-password-policy]').forEach(setup);
})();
