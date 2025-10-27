// Cronómetro AJAX para fichajes.php, solo sincroniza y pinta, no interfiere con dashboard.js
document.addEventListener('DOMContentLoaded', function() {
  const timerEl = document.getElementById('fichajeTimer');
  if (!timerEl) return;
  
  let estado = 'none', workSec = 0, pauseSec = 0, tInicio = 0, tPausa = 0, intId;
  const fmt = n => String(n).padStart(2,'0');
  
  function pintar() {
    const sWork  = workSec  + (estado==='working' ? ((Date.now()-tInicio)/1000|0) : 0);
    const sPause = pauseSec + (estado==='paused'  ? ((Date.now()-tPausa )/1000|0) : 0);
    const fontSize = window.innerWidth < 576 ? '1.2rem' : '1.1rem';
    timerEl.innerHTML = `<span class='text-primary'>${fmt(sWork/3600|0)}:${fmt(sWork/60%60|0)}:${fmt(sWork%60)}</span>` +
      (estado==='paused' ? `<br><div class='text-info fw-bold' style='font-size:${fontSize}; margin-top:0.5rem;'>☕ Pausa: ${fmt(sPause/3600|0)}:${fmt(sPause/60%60|0)}:${fmt(sPause%60)}</div>` : '');
  }
  
  function runTimer(){ clearInterval(intId); pintar(); intId = setInterval(pintar,1000); }
  
  fetch(BASE_URL +'fichaje/estado-fichaje.php?ts=' + Date.now(), { cache: 'no-store' })
    .then(r => r.json())
    .then(({state, workSec:ws, pauseSec:ps}) => {
      estado   = state;
      workSec  = ws;
      pauseSec = ps;
      // Solo resetear el timestamp del estado actual
      if (estado === 'working') {
          tInicio = Date.now();
      } else if (estado === 'paused') {
          tPausa = Date.now();
      }
      pintar();
      if (estado!=='none') runTimer();
    });
});
