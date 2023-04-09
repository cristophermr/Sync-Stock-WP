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
            update_post_meta($product_id, '_price', $product['price']);
            update_post_meta($product_id, '_syncstock-config', true);
            update_post_meta($product_id, '_stock', $product['stock_quantity']);
            update_post_meta($product_id, '_stock_status', $product['stock_quantity'] > 0 ? 'instock' : 'outofstock');

            echo '<p>Producto agregado: ' . $product['name'] . '</p>';
        } else {
            echo '<p>No se pudo agregar el producto: ' . $product['name'] . '</p>';
        }
    }
}