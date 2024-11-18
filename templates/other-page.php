<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if (!session_id()) {
    session_start();
}

if ( isset( $_GET['product_id'] ) ) {
    $product_id = intval( $_GET['product_id'] );
    $product = wc_get_product( $product_id );

    if ( $product && $product->is_type( 'variable' ) ) {
        $available_variations = $product->get_available_variations();
    } else {
        $available_variations = null;
    }
} else {
    $product_id = null;
    $available_variations = null;
}
?>

<div class="wrap">
    <h1>Product Details</h1>
    <p>This is another page within the New Plugin.</p>
    <a href="<?php echo esc_url( admin_url('admin.php?page=newplugin_dashboard') ); ?>" class="button">Back to Dashboard</a>

    <?php if ( $product_id && $available_variations ) : ?>
        <h2>Variations for Product ID: <?php echo $product_id; ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th scope="col" class="manage-column">Variation ID</th>
                <th scope="col" class="manage-column">SKU</th>
                <th scope="col" class="manage-column">Price</th>
                <th scope="col" class="manage-column">Stock Status</th>
                <th scope="col" class="manage-column">Variation Names</th>
                <th scope="col" class="manage-column">Variation Values</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ( $available_variations as $variation ) : ?>
                <tr>
                    <td><?php echo $variation['variation_id']; ?></td>
                    <td><?php echo $variation['sku']; ?></td>
                    <td><?php echo wc_price( $variation['display_price'] ); ?></td>
                    <td><?php echo $variation['is_in_stock'] ? 'In Stock' : 'Out of Stock'; ?></td>
                    <td>
                        <?php
                        $variation_attributes = $variation['attributes'];
                        $variation_names = array_keys($variation_attributes);
                        echo implode('<br>', array_map('wc_attribute_label', $variation_names));
                        ?>
                    </td>
                    <td>
                        <?php
                        $variation_values = array_values($variation_attributes);
                        echo implode('<br>', $variation_values);
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p>No product variations found or product is not a variable product.</p>
    <?php endif; ?>
</div>