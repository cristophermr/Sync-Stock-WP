<?php

namespace SirettSync;

use GuzzleHttp\Client;


class ActualizarStock
{
    private $domain = 'https://krudea.pcmasters.tech/api/wp/products';

    public function sync()
    {
        // Obtener los productos del servicio externo
        $products = $this->getProductsFromExternalService();

        // Verificar si se obtuvieron productos
        if (!empty($products)) {
            foreach ($products as $product) {
                // Verificar si el producto ya existe en WooCommerce
                $existing_product = $this->getProductBySku($product['sku']);

                if ($existing_product) {
                    // Si el producto ya existe, actualizar su stock
                    $this->updateProductStock($existing_product->get_id(), $product['stock']);
                } else {
                    // Si el producto no existe, agregarlo a WooCommerce
                    $this->addProductToWooCommerce($product);
                }
            }

            echo '<p>La sincronización de stock se ha completado exitosamente.</p>';
        } else {
            // Manejar caso deque no se obtengan productos del servicio externo
            echo '<p>No se encontraron productos en el servicio externo para sincronizar el stock.</p>';
        }
    }

    private function getProductsFromExternalService()
    {
        // Realizar la llamada al servicio externo para obtener los productos
        // Aquí iría la lógica para obtener los productos del servicio externo
        // y retornarlos en un arreglo

        // Ejemplo de llamada con GuzzleHTTP
        $client = new Client();
        $response = $client->get($this->domain
//            , [
//            'headers' => ['Authorization' => 'Bearer ' . $this->api_key
//            ]]
        );

        if ($response->getStatusCode() === 200) {
            $products = json_decode($response->getBody(), true);
            return $products;
        } else {
            return array();
        }
    }

    private function getProductBySku($sku)
    {
        // Obtener un producto de WooCommerce por su SKU
        $product = get_page_by_title($sku, OBJECT, 'product');

        return $product;
    }

    private function updateProductStock($product_id, $stock)
    {
        // Actualizar el stock de un producto en WooCommerce
        update_post_meta($product_id, '_stock', $stock);
        update_post_meta($product_id, '_stock_status', $stock > 0 ? 'instock' : 'outofstock');
    }

    private function addProductToWooCommerce($product)
    {
        // Agregar un producto a WooCommerce
        $new_product = array(
            'post_title' => $product['name'],
            'post_type' => 'product',
            'post_status' => 'publish'
        );

        $product_id = wp_insert_post($new_product);

        if ($product_id) {
            // Asignar SKU, precio y stock al producto
            update_post_meta($product_id, '_type', $product['type']);
            update_post_meta($product_id, '_sku', $product['sku']);
            update_post_meta($product_id, '_regular_price', $product['regular_price']);
            update_post_meta($product_id, '_price', $product['regular_price']);
            update_post_meta($product_id, '_manage_stock', $product['manage_stock']);
            update_post_meta($product_id, '_stock', $product['stock_quantity']);
            update_post_meta($product_id, '_stock_status', $product['stock_quantity'] > 0 ? 'instock' : 'outofstock');

            // Asignar imágenes al producto
            if (isset($product['images']) && is_array($product['images'])) {
                $product_images = array();
                foreach ($product['images'] as $image) {
                    if (isset($image['src'])) {
                        $image_id = $this->upload_product_image($image['src']);
                        if ($image_id) {
                            $product_images[] = $image_id;
                        }
                    }
                }
                update_post_meta($product_id, '_product_image_gallery', implode(',', $product_images));
                set_post_thumbnail($product_id, $product_images[0]);
            }

            echo '<p>Producto agregado: ' . $product['name'] . '</p>';
        } else {
            echo '<p>No se pudo agregar el producto: ' . $product['name'] . '</p>';
        }
    }

    private function upload_product_image($image_url)
    {
        // Descargar la imagen desde la URL
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        file_put_contents($file, $image_data);

        // Adjuntar la imagen a WordPress
        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}