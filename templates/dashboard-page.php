<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
if (!session_id()) {
    session_start();
}

if (isset($_SESSION['user_data'])) {
    $user_data = $_SESSION['user_data'];

} else {
    echo 'User not logged in.';
}
?>
<style>
    <style>
    .full-width-select {
        width: 100%;
        display: block;
        margin-bottom: 8px;
    }

    .xxx {
        display: flex;
        flex-direction: column;
    }

</style>
<div class="wrap">
    <h1>Dashboard</h1>
    <p>Welcome to the dashboard!</p>
    <a href="<?php echo esc_url( admin_url('admin.php?page=newplugin_other') ); ?>" class="button">Go to Other Page</a>
    <a href="<?php echo esc_url( admin_url('admin-post.php?action=newplugin_logout') ); ?>" class="button">Logout</a>
    <h2>Products</h2>
    <select id="selectCategories">
        <option disabled>Select a category</option>
    </select>
    <select id="selectSubCategories" hidden>
        <option disabled>Select a Sub category</option>
    </select>
    <select id="selectSubSubCategories" hidden>
        <option disabled>Select a sub Sub category</option>
    </select>
    <ul>
        <?php
        // Ensure WooCommerce functions are available
        if ( class_exists( 'WooCommerce' ) ) {
            // Get all products
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1
            );
            $products = new WP_Query( $args );

            if ( $products->have_posts() ) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th scope="col" id="checkbox" class="manage-column">#</th>';
                echo '<th scope="col" id="image" class="manage-column">Image</th>';
                echo '<th scope="col" id="title" class="manage-column column-title column-primary">Product</th>';
                echo '<th scope="col" id="category" class="manage-column">Category</th>';
                echo '<th scope="col" id="mapCategory" class="manage-column">Select Category</th>';

                echo '<th scope="col" id="price" class="manage-column">Price</th>';
                echo '<th scope="col" id="stock" class="manage-column">Stock Status</th>';
                echo '<th scope="col" id="view" class="manage-column">Actions</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ( $products->have_posts() ) {
                    $products->the_post();
                    global $product;
                    $image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'thumbnail' );
                    $terms = get_the_terms( $product->get_id(), 'product_cat' );
                    $categories = $terms ? join(', ', wp_list_pluck($terms, 'name')) : 'Uncategorized';

                    echo '<tr>';
                    echo '<td><input class="form-check-input" type="checkbox" value="" ></td>';
                    if ( $image_url && is_array( $image_url ) ) {
                        echo '<td><img src="' . esc_url( $image_url[0] ) . '" alt="' . get_the_title() . '" width="50" height="50"></td>';
                    } else {
                        echo '<td>No Image</td>';
                    }
                    echo '<td class="title column-title has-row-actions column-primary">' . get_the_title() . '</td>';
                    echo '<td>' . esc_html($categories) . '</td>';
                    echo '<td>
                        <div class="xxx">
                            <select class="tableSelectElements full-width-select category-select" data-row-id="' . $product->get_id() . '">
                            <option disabled >Select Category</option>
                            </select> 
                            <select class="full-width-select second-select" hidden>
                            <option disabled >Sub Category</option>
                            </select>
                            <select class="full-width-select third-select" hidden>
                                <option disabled>Sub Sub category</option>
                            </select> 
                            </div>
                          </td>';
                    echo '<td>' . $product->get_price_html() .'</td>';
                    echo '<td>' . ($product->is_in_stock() ? 'In Stock' : 'Out of Stock') . '</td>';
                    echo '<td>
                            <a href="' . esc_url( admin_url('admin.php?page=newplugin_other&product_id='.$product->get_id())) . '" class="button">View</a>
                            <button class="button sync-button" data-product-id="' . $product->get_id() . '">Sync</button>
                          </td>';
                    // echo '<td><a href="' . esc_url( admin_url('admin.php?page=newplugin_product_details&product_id=' . $product->get_id()) ) . '" class="button">View</a></td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                wp_reset_postdata();
                ?>
                <button class="button">Sync Selected Products</button>
        <?php
            } else {
                echo '<p>No products found.</p>';
            }
        } else {
            echo '<p>WooCommerce is not active.</p>';
        }
        ?>
    </ul>
</div>
<div>
    <?php
    if (isset($_SESSION['user_data'])) {
        $user_data = $_SESSION['user_data'];

//        print_r($user_data);
        $current_user = wp_get_current_user();
        $vendorID = $current_user->ID;
    } else {
        echo 'User not logged in.';
    }
    ?>
</div>

<script type="text/javascript">
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    var syncProductNonce = "<?php echo wp_create_nonce('sync_product_nonce'); ?>";
    var categories = [];

    function deactivateProduct(productId) {
        const productIds= [productId];
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'deactivate_product',
                product_id:productIds,
                security: syncProductNonce
            },
            dataType: 'json',
            success: function(response) {

                if(response['success']){
                    loadProducts();
                    Swal.fire({
                        title: "Success!",
                        text: "Product Deactivated Successfully.",
                        icon: "success",
                        confirmButtonText: "OK"
                    });

                }else{
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to deactivate the product. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }

            },
            error: function(xhr, status, error) {

                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong",
                    icon: "error",
                    confirmButtonText: "OK"
                });
                console.error("AJAX error:", xhr.responseText, "Status:", status, "Error:", error);
            }
        });
    }

    function activateProduct(productId) {
        const productIds= [productId];
        jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'activate_product',
                product_id:productIds,
                security: syncProductNonce
            },
            dataType: 'json',
            success: function(response) {

                if(response['success']){
                    loadProducts();
                    Swal.fire({
                        title: "Success!",
                        text: "Product activated Successfully.",
                        icon: "success",
                        confirmButtonText: "OK"
                    });

                }else{
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to deactivate the product. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }

            },
            error: function(xhr, status, error) {

                Swal.fire({
                    title: "Error!",
                    text: "Something went wrong",
                    icon: "error",
                    confirmButtonText: "OK"
                });
                console.error("AJAX error:", xhr.responseText, "Status:", status, "Error:", error);
            }
        });
    }


    function loadProducts(){

        const vendorID = <?php echo isset($vendorID) ? json_encode($vendorID) : 'null'; ?>;
        if (vendorID) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'get_wordpress_products_by_vendor',
                    vendor_id: vendorID,
                    security: '<?php echo wp_create_nonce("get_products_by_vendor_nonce"); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const external = response.data.data;

                        // Iterate through each row in the WordPress products table
                        jQuery('table.wp-list-table tbody tr').each(function() {
                            let productID ="";
                            let $button = "";

                            const syncButton = jQuery(this).find('.sync-button').data('product-id');
                            const deactivateButton = jQuery(this).find('.deactivate-button').data('product-id');
                            const activateButton = jQuery(this).find('.activate-button').data('product-id');


                            if(syncButton){
                                productID = syncButton;
                                $button = jQuery(this).find('.sync-button');
                            }else if (deactivateButton){
                                productID = deactivateButton;
                                $button = jQuery(this).find('.deactivate-button');
                            }else if (activateButton){
                                productID = activateButton;
                                $button = jQuery(this).find('.activate-button');
                            }

                            const x= external.find(function(a){
                                return a.productId===productID;
                            });
                            if (x) {
                                if(x.isActive === 1){
                                    $button.text('Deactivate');
                                    $button.removeClass('sync-button').addClass('deactivate-button');
                                    $button.off('click').on('click', function() {
                                        deactivateProduct(productID);
                                    });
                                }else if (x.isActive === 0){
                                    $button.text('Activate');
                                    $button.removeClass('sync-button').addClass('activate-button');
                                    $button.off('click').on('click', function() {
                                        activateProduct(productID);
                                    });
                                }

                            }
                        });
                        // You can add more code here to display the products on the page if needed.
                    } else {
                        Swal.fire({
                            title: "Error!",
                            text: "Failed to load products. Please try again.",
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to load products. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    });
                }
            });
        } else {
        }
    }
    jQuery(document).ready(function($) {
        loadProducts();

        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'get_categories',
                security: '<?php echo wp_create_nonce("get_categories_nonce"); ?>'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    categories = response.data.data;
                    const select = $('#selectCategories, .tableSelectElements');

                    select.empty();
                    select.append('<option selected disabled>Select a category</option>');
                    $.each(response.data.data, function(index, category) {
                        select.append('<option value="' + category.main_category_code
                            + '">' + category.main_category_name
                            + '</option>');
                    });
                } else {
                    console.log('Failed to load categories:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error: ', xhr, status, error);
                console.log('Response: ', xhr.responseText);
            }
        });

        $('#selectCategories').on('change', function() {
            document.getElementById('selectSubSubCategories').hidden=true;
            const z = document.getElementsByClassName('third-select');

            for(let i =0;i<z.length;i++){
                z[i].hidden=true;
            }
            const selectedCategoryCode = $(this).val();
            const selectedCategory = categories.find(category => category.main_category_code === selectedCategoryCode);

            const selectSubCategories = $('#selectSubCategories');
            selectSubCategories.empty();
            selectSubCategories.append('<option selected disabled>Select a Sub category</option>');

            $('#selectSubSubCategories').empty(); // Clear sub-sub-categories when new category is selected
            $('#selectSubSubCategories').append('<option selected disabled>Select a sub Sub category</option>');


            if (selectedCategory && selectedCategory.sub_category) {
                if(selectedCategory.sub_category.length>0){
                    document.getElementById('selectSubCategories').hidden=false;
                }else{
                    document.getElementById('selectSubCategories').hidden=true;
                }

                $.each(selectedCategory.sub_category, function(index, subCategory) {
                    selectSubCategories.append('<option value="' + subCategory.sub_category_code
                        + '">' + subCategory.sub_category_name
                        + '</option>');
                });
            }
        });

        $('#selectSubCategories').on('change', function() {
            const selectedCategoryCode = $('#selectCategories').val();
            const selectedCategory = categories.find(category => category.main_category_code === selectedCategoryCode);

            const selectedSubCategoryCode = $(this).val();
            const selectedSubCategory = selectedCategory.sub_category.find(subCat => subCat.sub_category_code === selectedSubCategoryCode);

            const selectSubSubCategories = $('#selectSubSubCategories');
            selectSubSubCategories.empty();
            selectSubSubCategories.append('<option selected disabled>Select a sub Sub category</option>');

            if (selectedSubCategory && selectedSubCategory.sub_sub_category) {
                if(selectedSubCategory.sub_sub_category.length>0){
                    document.getElementById('selectSubSubCategories').hidden=false;
                }else{
                    document.getElementById('selectSubSubCategories').hidden=true;
                }

                $.each(selectedSubCategory.sub_sub_category, function(index, subSubCategory) {
                    selectSubSubCategories.append('<option value="' + subSubCategory.sub_sub_category_code
                        + '">' + subSubCategory.sub_sub_category_name
                        + '</option>');
                });
            }
        });


        $('.sync-button').click(function() {
            const $row = $(this).closest('tr');
            const productId = $(this).data('product-id');
            const productDetails= [];

            // const productImage = $row.find('img').attr('src') || ''; // Get product image src
            // const productName = $row.find('.column-primary').text().trim(); // Get product name
            // const productPrice = $row.find('td').eq(5).text().trim();

            let firstSelectText = '', firstSelectVal = '';
            let secondSelectText = '', secondSelectVal = '';
            let thirdSelectText = '', thirdSelectVal = '';

            const $firstSelect = $row.find('.category-select');
            if ($firstSelect.length > 0 && $firstSelect.val()) {
                firstSelectText = $firstSelect.find('option:selected').text();
                firstSelectVal = $firstSelect.val();
            }

            const $secondSelect = $row.find('.second-select');
            if ($secondSelect.length > 0 && $secondSelect.is(':visible') && $secondSelect.val()) {
                secondSelectText = $secondSelect.find('option:selected').text();
                secondSelectVal = $secondSelect.val();
            }

            // Third select element
            const $thirdSelect = $row.find('.third-select');
            if ($thirdSelect.length > 0 && $thirdSelect.is(':visible') && $thirdSelect.val()) {
                thirdSelectText = $thirdSelect.find('option:selected').text();
                thirdSelectVal = $thirdSelect.val();
            }
            let mainCategory = firstSelectText;
            let mainCategoryCode = firstSelectVal;
            let subCategory = secondSelectText;
            let subCategoryCode = secondSelectVal;
            let subSubCategory = thirdSelectText;
            let subSubCategoryCode = thirdSelectVal;

            const payload = {
                productId:productId,
                mainCategory:mainCategory,
                mainCategoryCode:mainCategoryCode,
                subCategory:subCategory,
                subCategoryCode:subCategoryCode,
                subSubCategory:subSubCategory,
                subSubCategoryCode:subSubCategoryCode
            }

            productDetails.push(payload);


            console.log("#################");
            console.log(productDetails);
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'sync_product',
                    product_details:JSON.stringify(productDetails),

                    vendor_id:<?php echo isset($vendorID) ? json_encode($vendorID) : 'null'; ?>,
                    security: '<?php echo wp_create_nonce("sync_product_nonce"); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if(response['success']){
                        Swal.fire({
                            title: "Success!",
                            text: "Product synchronized successfully.",
                            icon: "success",
                            confirmButtonText: "OK"
                        });
                        loadProducts();
                    }else{
                        Swal.fire({
                            title: "Error!",
                            text: "Failed to synchronize the product. Please try again.",
                            icon: "error",
                            confirmButtonText: "OK"
                        });
                    }

                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to synchronize the product. Please try again.",
                        icon: "error",
                        confirmButtonText: "OK"
                    });

                }
            });
        });

    });

    function populateSubCategory(selectedElement, selectedValue) {
        const mainCategory = categories.find(cat => cat.main_category_code === selectedValue);

        if (mainCategory && mainCategory.sub_category) {
            const subCategorySelect = selectedElement.closest('td').querySelector('.second-select');
            if (subCategorySelect) {
                subCategorySelect.innerHTML = '<option disabled>Select a Sub category</option>';
                mainCategory.sub_category.forEach(subCat => {
                    const option = document.createElement('option');
                    option.value = subCat.sub_category_code;
                    option.textContent = subCat.sub_category_name;
                    subCategorySelect.appendChild(option);
                });
                subCategorySelect.hidden = false;
            }
        }
    }

    // Function to populate sub-subcategory <select> based on selected sub-category
    function populateSubSubCategory(selectedElement, selectedValue) {
        // Locate the main category and sub-category based on selected sub_category_code
        const mainCategory = categories.find(cat =>
            cat.sub_category.some(subCat => subCat.sub_category_code === selectedValue)
        );
        const subCategory = mainCategory ? mainCategory.sub_category.find(subCat => subCat.sub_category_code === selectedValue) : null;

        if (subCategory && subCategory.sub_sub_category) {
            const subSubCategorySelect = selectedElement.closest('td').querySelector('.third-select');
            if (subSubCategorySelect) {
                subSubCategorySelect.innerHTML = '<option disabled>Select a Sub Sub category</option>';
                subCategory.sub_sub_category.forEach(subSubCat => {
                    const option = document.createElement('option');
                    option.value = subSubCat.sub_sub_category_code;
                    option.textContent = subSubCat.sub_sub_category_name;
                    subSubCategorySelect.appendChild(option);
                });
                subSubCategorySelect.hidden = false;
            }
        }
    }

    // Event listeners for the first and second <select> elements
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.category-select').forEach(select => {
            select.addEventListener('change', function(event) {
                const selectedElement = event.target;
                const selectedValue = selectedElement.value;
                const subSubCategorySelect = document.getElementsByClassName('third-select');
                for(let i = 0; i<subSubCategorySelect.length;i++){
                    subSubCategorySelect[i].hidden=true;
                }
                populateSubCategory(selectedElement, selectedValue);
            });
        });

        document.querySelectorAll('.second-select').forEach(select => {
            select.addEventListener('change', function(event) {
                const selectedElement = event.target;
                const selectedValue = selectedElement.value;
                populateSubSubCategory(selectedElement, selectedValue);
            });
        });
    });
</script>
