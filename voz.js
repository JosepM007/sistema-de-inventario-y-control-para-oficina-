/* ═══════════════════════════════════════════════════
   OfficeStock Pro — Asistente de Voz Compartido
   Compatible con NVDA, JAWS, VoiceOver y SpeechSynthesis
   ═══════════════════════════════════════════════════ */

window.VOZ = (function () {
    const SS = window.speechSynthesis;
    let statusEl = null;

    function setStatus(txt, activo) {
        if (!statusEl) statusEl = document.getElementById('vozStatus');
        if (!statusEl) return;
        statusEl.textContent = txt;
        statusEl.className = 'voz-status' + (activo ? ' activo' : '');
    }

    function hablar(texto, encolar) {
        if (!SS) return;
        if (!encolar) SS.cancel();
        const utt = new SpeechSynthesisUtterance(texto);
        utt.lang = 'es-ES'; utt.rate = 0.92; utt.pitch = 1; utt.volume = 1;
        utt.onstart = () => setStatus('🔊 Hablando...', true);
        utt.onend   = () => setStatus('Listo', false);
        SS.speak(utt);
    }

    function detener() {
        if (SS) SS.cancel();
        setStatus('Detenido', false);
    }

    function ayudaGeneral(extras) {
        detener();
        const base = [
            'Ayuda del asistente de voz para personas con discapacidad visual.',
            'Botón Leer página: lee en voz alta toda la información de esta sección.',
            'Botón Ayuda: reproduce estas instrucciones.',
            'Botón Detener: para la voz inmediatamente.',
            'Usa la tecla Tab para navegar entre los controles.',
            'Usa Enter o Espacio para activar botones y enlaces.',
            'Un enlace "Saltar al contenido principal" está disponible al inicio para ir directo al contenido.'
        ];
        (extras ? base.concat(extras) : base).concat(['Fin de ayuda.']).forEach(t => hablar(t, true));
    }

    /* Anunciar campos de formulario al recibir foco */
    function activarFocoFormulario(mapa) {
        Object.entries(mapa).forEach(([id, msg]) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('focus', () => hablar(msg, true));
        });
    }

    return { hablar, detener, ayudaGeneral, activarFocoFormulario };
})();
