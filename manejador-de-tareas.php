<?php
/**
 * Plugin Name: Manejador de Tareas
 * Description: Plugin para gestionar tareas personalizadas.
 * Version: 1.0
 * Author: Juan Garcés
 */

if (!defined('ABSPATH')) exit;

// 1. Registrar CPT Tarea
add_action('init', function() {
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

// 2. Añadir metaboxes con campos personalizados
add_action('add_meta_boxes', function() {
    add_meta_box('campos_tarea', 'Detalles de la Tarea', 'render_campos_tarea', 'tarea', 'normal', 'high');
});

function render_campos_tarea($post) {
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

// 3. Guardar los metadatos
add_action('save_post', function($post_id) {
    if (isset($_POST['prioridad'])) {
        update_post_meta($post_id, '_prioridad', sanitize_text_field($_POST['prioridad']));
    }
    if (isset($_POST['vencimiento'])) {
        update_post_meta($post_id, '_vencimiento', sanitize_text_field($_POST['vencimiento']));
    }
});

// 4. Shortcode [lista_tareas]
add_shortcode('lista_tareas', function() {
    $query = new WP_Query([
        'post_type' => 'tarea',
        'posts_per_page' => -1,
        'meta_key' => '_prioridad',
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ]);

    if (!$query->have_posts()) return '<p>No hay tareas pendientes.</p>';

    $output = '<ul>';
    while ($query->have_posts()) {
        $query->the_post();
        $prioridad = get_post_meta(get_the_ID(), '_prioridad', true);
        $vencimiento = get_post_meta(get_the_ID(), '_vencimiento', true);
        $output .= "<li><strong>" . get_the_title() . "</strong> — Prioridad: {$prioridad}, Vence: {$vencimiento}</li>";
    }
    $output .= '</ul>';

    wp_reset_postdata();
    return $output;
});
