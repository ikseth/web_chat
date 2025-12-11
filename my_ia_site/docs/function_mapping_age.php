function get_mpaa_level_by_age($edad, $mpaa_levels_file = '/ruta/a/mpaa_levels.json') {
    $levels = json_decode(file_get_contents($mpaa_levels_file), true);
    // Ordena por edad mínima descendente (de mayor a menor)
    usort($levels, function($a, $b) {
        return $b['edad_min'] - $a['edad_min'];
    });
    foreach ($levels as $level) {
        if ($edad >= $level['edad_min']) {
            return $level;
        }
    }
    // Si no encaja, devuelve el más bajo (por defecto)
    return $levels[count($levels)-1];
}

