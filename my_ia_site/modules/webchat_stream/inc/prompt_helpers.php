<?php
// Utilidad para calcular edad
function calcular_edad($fecha_nacimiento) {
    $fn = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    return $fn->diff($hoy)->y;
}

// Cargar configuraciones desde ficheros externos
function cargar_json_config($ruta) {
    return json_decode(file_get_contents($ruta), true);
}

// Obtener el nivel MPAA aplicable según edad
function obtener_mpaa_para_edad($edad, $mpaa_levels) {
    // Ordena de mayor a menor, usa el primero cuyo edad_min <= edad
    usort($mpaa_levels, fn($a, $b) => $b['edad_min'] <=> $a['edad_min']);
    foreach ($mpaa_levels as $nivel) {
        if ($edad >= $nivel['edad_min']) return $nivel;
    }
    // Si ninguno aplica, devuelve el más restrictivo
    return $mpaa_levels[count($mpaa_levels)-1];
}

// Construir el prompt final
function construir_prompt_final($usuario, $user_data, $rol, $experto, $idioma, $pregunta) {
    // Configuraciones externas
    $mpaa_levels = cargar_json_config(__DIR__.'/../../../config/mpaa_levels.json');
    if (!file_exists(__DIR__.'/../../../config/mpaa_levels.json')) {
    	error_log("Falta el archivo mpaa_levels.json");
    }
    $roles = cargar_json_config(__DIR__.'/../../../config/roles.json');
    if (!file_exists(__DIR__.'/../../../config/roles.json')) {
    	error_log("Falta el archivo roles.json");
    }
    $experts = cargar_json_config(__DIR__.'/../config/experts.json');
    if (!file_exists(__DIR__.'/../config/experts.json')) {
    	error_log("Falta el archivo experts.json");
    }

    // Edad y nivel MPAA
    $fecha_nac = $user_data['fecha_nacimiento'] ?? '';
    $edad = $fecha_nac ? calcular_edad($fecha_nac) : 99;
    $mpaa = obtener_mpaa_para_edad($edad, $mpaa_levels);
    $prompt_mpaa = $mpaa['prompt'] ?? '';

    // Prompt por rol
    $rol_data = array_filter($roles, fn($r) => $r['id'] === $rol);
    $prompt_rol = '';
    if ($rol_data) {
        $rol_data = array_values($rol_data)[0];
        $prompt_rol = $rol_data['prompt_base'] ?? '';
    }

    // Prompt experto/contexto
    $expert_data = array_filter($experts, fn($e) => $e['id'] === $experto);
    $prompt_expert = '';
    if ($expert_data) {
        $expert_data = array_values($expert_data)[0];
        $prompt_expert = $expert_data['prompt'] ?? '';
    }

    // Idioma
    $prompt_idioma = '';
    if ($idioma === 'es') $prompt_idioma = "Responde únicamente en español. 
No debes traducir tu respuesta, ni repetirla en otros idiomas, ni añadir bloques informativos en inglés u otras lenguas. 
Tu respuesta debe estar totalmente en español y no debe incluir ninguna frase, etiqueta, glosa o traducción secundaria.
";
    if ($idioma === 'en') $prompt_idioma = "Answer only in English.";

    // Monta prompt final

	$instruccion_final = "Responde solo a la siguiente pregunta y detente después de una única respuesta clara y cerrada. No generes preguntas adicionales, ejemplos ni continuaciones.";

	// Combina pregunta con la instrucción, sin marcarla como "Pregunta:"
	$bloque_pregunta = $instruccion_final . "\n\n" . trim($pregunta);

	// Monta prompt final robusto
	$bloques = array_filter([
	    trim($prompt_mpaa),
	    trim($prompt_rol),
	    trim($prompt_expert),
	    trim($prompt_idioma),
	    $bloque_pregunta
	]);

    return implode("\n\n", $bloques);
}
?>

