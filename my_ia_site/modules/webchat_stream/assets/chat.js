document.addEventListener('DOMContentLoaded', function() {
   let vantaEffect = null;
   const bgEl = document.getElementById('vanta-bg');

   function waitForVantaInit(retries = 10) {
	if (bgEl && bgEl.vantaEffect) {
		vantaEffect = bgEl.vantaEffect;
	} else if (retries > 0) {
		setTimeout(() => waitForVantaInit(retries - 1), 100);
	} else {
		console.warn("VANTA.NET no se inicializó a tiempo.");
	}
    }

    waitForVantaInit();

    const chatWindow = document.getElementById('chat-window');
    const chatForm = document.getElementById('chat-form');
    const userInput = document.getElementById('user-input');
    const loader = document.getElementById('chat-loader');
    const btnEs = document.getElementById('lang-es');
    const btnEn = document.getElementById('lang-en');
    const formatSelect = document.getElementById('format-select');
    const expertSelect = document.getElementById('expert-select');
    let lang = getCookie('lang') || 'es';
    let experts = [];
    let roles = [];
    let userRole = (typeof window.USER_ROLE !== "undefined") ? window.USER_ROLE : "user";


    function setVantaThinking() {
	if (!vantaEffect) return;
	vantaEffect.setOptions({
		color: 0xff5500,
		backgroundColor: 0x220000,
		points: 16.0,
		maxDistance: 32.0,
		spacing: 14.0
	});
    }

    function setVantaResponding() {
	if (!vantaEffect) return;
	vantaEffect.setOptions({
		color: 0x00cc66,
		backgroundColor: 0x001a00,
		points: 10.0,
		maxDistance: 24.0,
		spacing: 20.0
	});
    }

    function resetVanta() {
	if (!vantaEffect) return;
	vantaEffect.setOptions({
		color: 0x2266aa,
		backgroundColor: 0x000000,
		points: 10.0,
		maxDistance: 22.0,
		spacing: 18.0
	});
    }

    // Cargar expertos desde JSON
    function loadExperts(callback) {
        fetch('/modules/webchat_stream/config/experts.json')
            .then(resp => resp.json())
            .then(data => {
                experts = data;
                callback();
            });
    }

    // Cargar roles desde JSON
    function loadRoles(callback) {
        fetch('/config/roles.json')
            .then(resp => resp.json())
            .then(data => {
                roles = data;
                callback();
            });
    }

    // Poblar selects según rol
    function setupAtributosPorRol() {
        // 1. Buscar el rol activo
        const rol = roles.find(r => r.id === userRole) || roles.find(r => r.id === "user");
        // 2. Limpiar y poblar expertos
        expertSelect.innerHTML = "";
        rol.expertos_permitidos.forEach(expId => {
            const expObj = experts.find(e => e.id === expId);
            if (expObj) {
                const opt = document.createElement('option');
                opt.value = expObj.id;
                opt.textContent = expObj.nombre;
                expertSelect.appendChild(opt);
            }
        });
        // 3. Limpiar y poblar formatos
        formatSelect.innerHTML = "";
        rol.formatos_permitidos.forEach(fmt => {
            const opt = document.createElement('option');
            opt.value = fmt;
            opt.textContent = fmt.charAt(0).toUpperCase() + fmt.slice(1);
            formatSelect.appendChild(opt);
        });
    }

    // -- Carga atributos dinámicamente (roles + expertos)
    loadRoles(() => {
        loadExperts(() => {
            setupAtributosPorRol();
        });
    });

    // Recuperar historial (si existe)
    if (sessionStorage.getItem('webchat_history')) {
        chatWindow.innerHTML = sessionStorage.getItem('webchat_history');
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }

    function setLang(newLang) {
        lang = newLang;
        document.cookie = "lang=" + lang + "; path=/";
        btnEs.classList.toggle('active', lang === 'es');
        btnEn.classList.toggle('active', lang === 'en');
    }

    btnEs.addEventListener('click', function(e) {
        e.preventDefault();
        setLang('es');
        userInput.focus();
    });
    btnEn.addEventListener('click', function(e) {
        e.preventDefault();
        setLang('en');
        userInput.focus();
    });

    function getCookie(name) {
        let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    // --- PROMPT DINÁMICO SEGÚN ATRIBUTOS ---
    function buildPrompt(question) {
        let attrs = [];
        if (lang === 'es') attrs.push('idioma=español');
        if (lang === 'en') attrs.push('idioma=inglés');

        const format = formatSelect.value;
        attrs.push('formato=' + format);

        const expertId = expertSelect.value;
        const experto = experts.find(e => e.id === expertId);

        if (experto && experto.id !== 'general') {
            attrs.push('experto=' + experto.id);
        }

        // Prompt base por rol
        const rol = roles.find(r => r.id === userRole);
	setVantaThinking();
        let prompt = '[' + attrs.join('] [') + ']\n';
        if (rol && rol.prompt_base) {
            prompt += rol.prompt_base + '\n';
        }
        if (experto && experto.prompt) {
            prompt += experto.prompt + '\n';
        }
        prompt += "" + question;
        return prompt;
    }

    // --- MARKDOWN SIMPLE ---
    function simpleMarkdown(str) {
        // Negrita: **texto**
        str = str.replace(/\*\*(.*?)\*\*/g, "<b>$1</b>");
        // Cursiva: *texto*
        str = str.replace(/\*(.*?)\*/g, "<i>$1</i>");
        return str.replace(/\n/g, "<br>");
    }

    function appendMessage(text, who = 'user') {
        const msg = document.createElement('div');
        msg.className = 'chat-msg ' + who;
        msg.textContent = text;
        chatWindow.appendChild(msg);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        saveHistory();
    }

    function saveHistory() {
        sessionStorage.setItem('webchat_history', chatWindow.innerHTML);
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        let question = userInput.value.trim();
        if (!question) return;

        let prompt = buildPrompt(question);

        appendMessage(userInput.value, 'user');
        userInput.value = '';
        userInput.disabled = true;

        const aiMsg = document.createElement('div');
        aiMsg.className = 'chat-msg ai streaming';
        aiMsg.textContent = '';
        chatWindow.appendChild(aiMsg);
        chatWindow.scrollTop = chatWindow.scrollHeight;
        saveHistory();

        fetch('/modules/webchat_stream/inc/proxy_stream.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({text: prompt}),
        })
        .then(response => {
	    setVantaResponding();
            if (!response.body || !window.ReadableStream) throw new Error("El navegador no soporta streaming");
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = "";
            function pump() {
                return reader.read().then(({done, value}) => {
                    if (done) {
			resetVanta();
                        aiMsg.classList.remove('streaming');
                        userInput.disabled = false;
                        userInput.focus();
                        saveHistory();
                        return;
                    }
                    buffer += decoder.decode(value, {stream: true});
                    let cleanBuffer = buffer.replace(/^[\s\n]+/, '');
                    aiMsg.innerHTML = simpleMarkdown(cleanBuffer);
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                    saveHistory();
                    return pump();
                });
            }
            return pump();
        })
        .catch(err => {
	    resetVanta();
            aiMsg.textContent = "[Error de comunicación: " + err.message + "]";
            aiMsg.classList.remove('streaming');
	    loader.classList.add('hidden');
            userInput.disabled = false;
            saveHistory();
        });
    });

    setLang(lang);
});

