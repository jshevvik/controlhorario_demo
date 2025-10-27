(function(){
      const maxDias = 22;
      let seleccion = new Set();

      const contadorEl = document.getElementById('contador');
      const inputsEl   = document.getElementById('inputs-fechas');
      const btnSubmit  = document.getElementById('btn-submit');

      document.querySelectorAll('td.selectable').forEach(td => {
        td.addEventListener('click', () => {
          const date = td.dataset.date;
          if (seleccion.has(date)) {
            // deseleccionar
            seleccion.delete(date);
            td.classList.remove('selected');
            inputsEl.querySelector(`input[value="${date}"]`)?.remove();
          } else {
            // seleccionar (si no supera el límite)
            if (seleccion.size >= maxDias) {
              alert(`Sólo puedes seleccionar hasta ${maxDias} días laborables.`);
              return;
            }
            seleccion.add(date);
            td.classList.add('selected');
            // crear input oculto
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'vacaciones[]';
            inp.value = date;
            inputsEl.appendChild(inp);
          }
          // actualizar contador y estado del botón
          contadorEl.textContent = seleccion.size;
          btnSubmit.disabled = (seleccion.size === 0);
        });
      });
    })();