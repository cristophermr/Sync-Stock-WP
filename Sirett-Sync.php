<?php

/*
Plugin Name: PC Masters Stock Sync
Plugin URI: https://www.econsulting.co.cr
Description: Este Plugin sincroniza los productos de toda la tienda, ademas cada 1 minuto es capaz de revisar el stock.
Version: 1.0.0.0
Author: Christopher Morales R
Author URI: https://www.econsulting.co.cr
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Autoload para cargar las clases del plugin
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use SirettSync\ActualizarStock;
//
//// Iniciar la sincronización del stock
//add_action('init', function () {
//    $sync_stock = new ActualizarStock();
//    $sync_stock->sync();
//});

// Agregar una página de configuración al menú de WordPress
add_action('admin_menu', function () {
    add_options_page('Configuración de SyncStock', 'SyncStock', 'manage_options', 'syncstock-config', 'syncstock_config_page');
    add_options_page('Borrar Productos', 'DeleteStock', 'manage_options', 'deletestock-config', 'delete_products');
});

// Página de configuración del plugin
function syncstock_config_page()
{
        // Verificar si se ha hecho clic en el botón de actualización
        if (isset($_POST['syncstock_update'])) {
            // Realizar la actualización del stock
            $sync_stock = new ActualizarStock();
            $sync_stock->sync();

            // Mostrar un mensaje de éxito
            echo '<div class="updated"><p>El stock se ha actualizado correctamente.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Actualizar Stock</h1>
            <p>Haz clic en el botón para actualizar el stock de los productos.</p>
            <form method="post">
                <input type="hidden" name="syncstock_update" value="1">
                <?php submit_button('Actualizar Stock', 'primary', 'syncstock_update_button', false); ?>
            </form>
        </div>
        <?php
}
