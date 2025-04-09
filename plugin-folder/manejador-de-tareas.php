<?php

/**
 * Plugin Name: Manejador de Tareas
 * Description: Plugin para gestionar tareas personalizadas con estilos modernos.
 * Version: 1.1
 * Author: Juan Garcés
 */

if (!defined('ABSPATH')) exit;

// 1. Registrar CPT Tarea
add_action('init', function () {
    register_post_type('tarea', [
        'labels' => [
            'name' => 'Tareas',
            'singular_name' => 'Tarea',
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-list-view'
    ]);
});

// 2. Metaboxes para prioridad y vencimiento
add_action('add_meta_boxes', function () {
    add_meta_box('campos_tarea', 'Detalles de la Tarea', 'render_campos_tarea', 'tarea', 'normal', 'high');
});

function render_campos_tarea($post)
{
    $prioridad = get_post_meta($post->ID, '_prioridad', true);
    $vencimiento = get_post_meta($post->ID, '_vencimiento', true);
?>
    <p>
        <label>Prioridad:</label>
        <select name="prioridad">
            <option value="alta" <?php selected($prioridad, 'alta'); ?>>Alta</option>
            <option value="media" <?php selected($prioridad, 'media'); ?>>Media</option>
            <option value="baja" <?php selected($prioridad, 'baja'); ?>>Baja</option>
        </select>
    </p>
    <p>
        <label>Fecha de vencimiento:</label><br>
        <input type="date" name="vencimiento" value="<?php echo esc_attr($vencimiento); ?>" />
    </p>
<?php
}

// 3. Guardar los campos personalizados
add_action('save_post', function ($post_id) {
    if (isset($_POST['prioridad'])) {
        update_post_meta($post_id, '_prioridad', sanitize_text_field($_POST['prioridad']));
    }
    if (isset($_POST['vencimiento'])) {
        update_post_meta($post_id, '_vencimiento', sanitize_text_field($_POST['vencimiento']));
    }
});

// 4. Shortcode [lista_tareas] con Tailwind
add_shortcode('lista_tareas', function () {
    // Captura segura del filtro por prioridad
    $prioridad_filtrada = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';

    $args = [
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_key' => '_prioridad',
    ];

    if ($prioridad_filtrada) {
        $args['meta_query'] = [
            [
                'key' => '_prioridad',
                'value' => $prioridad_filtrada,
                'compare' => '=',
            ]
        ];
    }

    $query = new WP_Query($args);

    ob_start();

    static $tailwind_loaded = false;
    if (!$tailwind_loaded) {
        echo '<script src="https://cdn.tailwindcss.com"></script>';
        $tailwind_loaded = true;
    }

    // Mostrar el formulario de filtro
    echo '<form method="get" class="mb-6">';
    echo '<label class="block mb-2 text-sm font-medium text-gray-700">Filtrar por prioridad:</label>';
    echo '<div class="flex gap-2">';
    echo '<select name="prioridad" class="w-4/5 border border-gray-300 rounded p-2 text-sm">';
    echo '<option value="">Todas</option>';
    echo '<option value="alta"' . selected($prioridad_filtrada, 'alta', false) . '>Alta</option>';
    echo '<option value="media"' . selected($prioridad_filtrada, 'media', false) . '>Media</option>';
    echo '<option value="baja"' . selected($prioridad_filtrada, 'baja', false) . '>Baja</option>';
    echo '</select>';
    echo '<button type="submit" class="w-1/5 px-4 py-2 bg-black text-white text-sm rounded hover:bg-gray-800 transition">Filtrar</button>';
    echo '</div>';
    echo '</form>';


    if (!$query->have_posts()) {
        echo '<p class="text-gray-500">No hay tareas para esta prioridad.</p>';
    } else {
        echo '<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">';
        while ($query->have_posts()) {
            $query->the_post();
            $prioridad = get_post_meta(get_the_ID(), '_prioridad', true);
            $vencimiento = get_post_meta(get_the_ID(), '_vencimiento', true);

            $color = match ($prioridad) {
                'alta' => 'border-red-500 text-red-700',
                'media' => 'border-yellow-500 text-yellow-700',
                'baja' => 'border-green-500 text-green-700',
                default => 'border-gray-400 text-gray-600',
            };

            echo '<div class="border-l-4 ' . $color . ' bg-white shadow-md rounded-md p-4">';
            echo '<h3 class="text-lg font-semibold mb-1">' . get_the_title() . '</h3>';
            echo '<p class="text-sm">Prioridad: <strong>' . ucfirst($prioridad) . '</strong></p>';
            echo '<p class="text-sm">Vencimiento: ' . esc_html($vencimiento) . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});
