<?php
/**
 * Mini-cart
 *
 * Contains the markup for the mini-cart, used by the cart widget.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/mini-cart.php.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 10.6.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_mini_cart' ); ?>

<?php if ( ! WC()->cart->is_empty() ) : ?>

    <?php
    // Calculate Free Shipping Progress
    $free_shipping_threshold = 200000;
    $current_subtotal = WC()->cart->get_subtotal();
    $amount_left = $free_shipping_threshold - $current_subtotal;
    $progress_percentage = ( $current_subtotal / $free_shipping_threshold ) * 100;
    if ( $progress_percentage > 100 ) {
        $progress_percentage = 100;
    }
    ?>

    <!-- Free Shipping Progress Bar -->
    <div class="mb-8 bg-white dark:bg-[#181818] p-5 rounded-2xl border border-gray-200/50 dark:border-gray-800/50 shadow-[0_4px_20px_rgba(0,0,0,0.03)] dark:shadow-none relative overflow-hidden">
        <?php if ( $amount_left > 0 ) : ?>
            <p class="text-xs text-center text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider mb-2">
                Te faltan <span class="text-gray-900 dark:text-white font-bold"><?php echo wc_price( $amount_left ); ?></span> para <span class="text-black dark:text-white">Envío Gratis</span>
            </p>
        <?php else : ?>
            <p class="text-xs text-center text-green-600 dark:text-green-400 font-bold uppercase tracking-wider mb-2">
                ¡Tienes Envío Gratis!
            </p>
        <?php endif; ?>

        <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden mt-1 shadow-inner">
            <div class="bg-black dark:bg-white h-full rounded-full transition-all duration-1000 ease-[cubic-bezier(0.4,0,0.2,1)]" style="width: <?php echo esc_attr( $progress_percentage ); ?>%;"></div>
        </div>

        <?php if ( $amount_left > 0 ) : ?>
            <div class="text-center mt-2">
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors underline decoration-1 underline-offset-2">Ir a la tienda</a>
            </div>
        <?php endif; ?>
    </div>

    <ul class="woocommerce-mini-cart cart_list product_list_widget flex flex-col gap-6 w-full <?php echo esc_attr( $args['list_class'] ?? '' ); ?>">
        <?php
        do_action( 'woocommerce_before_mini_cart_contents' );

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                $thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail', array('class' => 'absolute inset-0 w-full h-full object-cover')), $cart_item, $cart_item_key );
                $product_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

                // Get Rareza to apply subtle background class if needed, or keep clean.
                $rareza_terms = get_the_terms( $product_id, 'lh_rareza' );
                $glow_class = '';
                if ( $rareza_terms && ! is_wp_error( $rareza_terms ) ) {
                    $slug = $rareza_terms[0]->slug;
                    if ( $slug === 'nicho' ) $glow_class = 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-900/30';
                    elseif ( $slug === 'arabe' ) $glow_class = 'bg-purple-50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-900/30';
                    elseif ( $slug === 'disenador' ) $glow_class = 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-900/30';
                    elseif ( $slug === 'accesible' ) $glow_class = 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-900/30';
                }

                // Default clean classes if no rareza specific bg needed
                $card_class = $glow_class ? $glow_class : 'bg-white dark:bg-[#1a1a1a] border-gray-200/60 dark:border-gray-800/60';
                ?>
                <li class="woocommerce-mini-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key ) ); ?> flex gap-5 p-4 sm:p-5 rounded-2xl border shadow-[0_8px_30px_rgba(0,0,0,0.04)] dark:shadow-none relative <?php echo esc_attr($card_class); ?> transition-shadow duration-300 hover:shadow-[0_12px_40px_rgba(0,0,0,0.08)] transform-gpu will-change-transform">

                    <!-- Rarity Pill Integrated (Top Left) -->
                    <?php if ( $rareza_terms && ! is_wp_error( $rareza_terms ) ) :
                        $term = $rareza_terms[0];
                        $slug = $term->slug;
                        $pill_classes = 'absolute -top-3 -left-3 inline-flex items-center justify-center px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-[0.25em] text-white shadow-md z-20';
                        if ( $slug === 'nicho' ) $pill_classes .= ' bg-gradient-to-r from-yellow-400 to-yellow-600 animate-pulse-glow-gold';
                        elseif ( $slug === 'arabe' ) $pill_classes .= ' bg-gradient-to-r from-purple-500 to-purple-800 animate-pulse-glow-purple';
                        elseif ( $slug === 'disenador' ) $pill_classes .= ' bg-gradient-to-r from-blue-400 to-blue-700 animate-pulse-glow-blue';
                        else $pill_classes .= ' bg-gradient-to-r from-emerald-400 to-emerald-700 animate-pulse-glow-green';
                    ?>
                        <div class="<?php echo esc_attr( $pill_classes ); ?>">
                            <span class="relative z-10"><?php echo esc_html( $term->name ); ?></span>
                            <!-- Removed mix-blend-overlay to fix Chrome graphical tearing glitch -->
                            <div class="absolute inset-0 bg-white opacity-20"></div>
                        </div>
                    <?php endif; ?>

                    <!-- Remove Item (Sleek Circle) -->
                    <div class="absolute -top-3 -right-3 z-20">
                        <?php
                        echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            'woocommerce_cart_item_remove_link',
                            sprintf(
                                '<a href="%s" class="remove remove_from_cart_button bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-400 hover:text-red-500 hover:border-red-200 dark:hover:text-red-400 dark:hover:border-red-900 rounded-full w-7 h-7 flex items-center justify-center text-sm font-bold transition-transform duration-300 shadow-md hover:scale-110 transform-gpu" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s" style="line-height:1;">&times;</a>',
                                esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
                                /* translators: %s is the product name */
                                esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $product_name ) ) ),
                                esc_attr( $product_id ),
                                esc_attr( $cart_item_key ),
                                esc_attr( $_product->get_sku() )
                            ),
                            $cart_item_key
                        );
                        ?>
                    </div>

                    <!-- Product Image Strict Aspect Ratio -->
                    <!-- Sibling structure: <a> and <img> must be absolute siblings to prevent layout collapse -->
                    <!-- Flex constraints explicitly set on the wrapper to prevent Safari from shrinking the image -->
                    <div class="flex-none" style="flex: 0 0 96px; width: 96px; min-width: 96px; max-width: 96px; display: block;">
                        <div class="relative w-full aspect-[3/4] overflow-hidden mb-0 rounded-xl shadow-sm border border-black/5 dark:border-white/5 bg-gray-100 dark:bg-gray-800">
                            <?php if ( ! empty( $product_permalink ) ) : ?>
                                <a href="<?php echo esc_url( $product_permalink ); ?>" class="absolute inset-0 z-10 w-full h-full"></a>
                            <?php endif; ?>
                            <?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="flex flex-col justify-between py-1.5 flex-grow min-w-0">
                        <div>
                            <!-- Brand (If exists, extra luxury detail) -->
                            <?php
                            $marca_terms = get_the_terms( $product_id, 'lh_marca' );
                            if ( $marca_terms && ! is_wp_error( $marca_terms ) ) {
                                echo '<div class="text-[9px] font-black uppercase tracking-[0.3em] text-gray-400 dark:text-gray-500 mb-1">' . esc_html( $marca_terms[0]->name ) . '</div>';
                            }
                            ?>
                            <h3 class="text-sm font-black tracking-wide text-gray-900 dark:text-white leading-tight">
                                <?php if ( empty( $product_permalink ) ) : ?>
                                    <?php echo wp_kses_post( $product_name ); ?>
                                <?php else : ?>
                                    <a href="<?php echo esc_url( $product_permalink ); ?>" class="hover:underline">
                                        <?php echo wp_kses_post( $product_name ); ?>
                                    </a>
                                <?php endif; ?>
                            </h3>
                            <?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                                <?php echo $product_price; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>

                        <!-- Quantity and Subtotal -->
                        <div class="flex items-end justify-between mt-4">

                            <!-- Custom Quantity Selector (Rounded pill shape) -->
                            <!-- Replaced backdrop-blur with solid bg to fix Chrome tearing -->
                            <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-full overflow-hidden bg-gray-50 dark:bg-gray-900 h-8 shadow-sm transform-gpu">
                                <button type="button" class="lhparfum-qty-btn lhparfum-qty-minus w-8 h-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 hover:text-black dark:hover:text-white transition-colors" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" data-action="minus">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                </button>

                                <input type="number" class="lhparfum-qty-input w-8 h-full text-center text-xs font-bold bg-transparent border-none p-0 text-gray-900 dark:text-white appearance-none focus:ring-0 cursor-default pointer-events-none" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="0" max="<?php echo esc_attr( $_product->get_max_purchase_quantity() > 0 ? $_product->get_max_purchase_quantity() : '' ); ?>" step="1" readonly />

                                <button type="button" class="lhparfum-qty-btn lhparfum-qty-plus w-8 h-full flex items-center justify-center text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-800 hover:text-black dark:hover:text-white transition-colors" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" data-action="plus">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </div>

                            <div class="font-bold text-gray-900 dark:text-white text-[15px] leading-none ml-2 text-right">
                                <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </div>
                        </div>
                    </div>
                </li>
                <?php
            }
        }

        do_action( 'woocommerce_mini_cart_contents' );
        ?>
    </ul>

    <?php
    // Find the highest priced item in the cart to determine the button rarity color
    $highest_price = 0;
    $highest_rarity = '';

    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
        $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

        $price = floatval( $_product->get_price() );
        if ( $price > $highest_price ) {
            $highest_price = $price;
            $rareza_terms = get_the_terms( $product_id, 'lh_rareza' );
            if ( $rareza_terms && ! is_wp_error( $rareza_terms ) ) {
                $highest_rarity = $rareza_terms[0]->slug;
            }
        }
    }

    $btn_glow_class = '';
    $btn_bg_class = 'bg-black dark:bg-white text-white dark:text-white hover:bg-gray-900 dark:hover:bg-gray-200'; // Default

    // We recreate the same CSS effects used in header.php for the custom-add-to-cart-wrapper
    if ( $highest_rarity === 'nicho' ) {
        $btn_glow_class = 'animate-pulse-glow-gold';
        $btn_bg_class = 'text-white border-0';
        $btn_bg_style = 'background: linear-gradient(to right, #fbbf24, #d97706);';
    } elseif ( $highest_rarity === 'arabe' ) {
        $btn_glow_class = 'animate-pulse-glow-purple';
        $btn_bg_class = 'text-white border-0';
        $btn_bg_style = 'background: linear-gradient(to right, #a855f7, #7e22ce);';
    } elseif ( $highest_rarity === 'disenador' ) {
        $btn_glow_class = 'animate-pulse-glow-blue';
        $btn_bg_class = 'text-white border-0';
        $btn_bg_style = 'background: linear-gradient(to right, #60a5fa, #1d4ed8);';
    } elseif ( $highest_rarity === 'accesible' ) {
        $btn_glow_class = 'animate-pulse-glow-green';
        $btn_bg_class = 'text-white border-0';
        $btn_bg_style = 'background: linear-gradient(to right, #34d399, #047857);';
    } else {
        $btn_bg_style = '';
    }
    ?>

    <div class="sticky bottom-0 left-0 right-0 bg-[#fcfcfc]/90 dark:bg-[#111111]/90 backdrop-blur-xl pt-6 pb-2 sm:pb-0 mt-8 border-t border-gray-200/60 dark:border-gray-800/60 z-30 -mx-6 sm:-mx-8 px-6 sm:px-8">
        <?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>

        <div class="flex justify-between items-end mb-6">
            <span class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500"><?php esc_html_e( 'Subtotal', 'woocommerce' ); ?></span>
            <span class="text-2xl font-black tracking-tight text-gray-900 dark:text-white leading-none"><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
        </div>

        <div class="woocommerce-mini-cart__buttons buttons flex flex-col gap-3">
            <?php
                $cart_count = WC()->cart->get_cart_contents_count();
                $btn_text = $cart_count > 1 ? 'Adquirir fragancias' : 'Adquirir fragancia';
            ?>
            <!-- Luxury glow button, overriding checkout text to Adquirir fragancia as requested -->
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="button checkout wc-forward w-full text-center py-4 font-black uppercase tracking-[0.2em] text-[11px] transition-colors duration-300 rounded-full shadow-[0_10px_30px_rgba(0,0,0,0.15)] dark:shadow-[0_10px_30px_rgba(255,255,255,0.15)] transform-gpu <?php echo esc_attr($btn_bg_class . ' ' . $btn_glow_class); ?>" style="<?php echo esc_attr($btn_bg_style); ?>"><?php echo esc_html( $btn_text ); ?></a>
        </div>

        <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
    </div>

<?php else : ?>

    <div class="flex flex-col items-center justify-center h-full text-center py-20">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
        <p class="text-gray-500 dark:text-gray-400 uppercase tracking-widest text-sm font-semibold mb-8">
            <?php esc_html_e( 'Tu bolsa está vacía.', 'bruiser-tech-lhparfum' ); ?>
        </p>
        <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>" class="button border border-gray-900 dark:border-white text-gray-900 dark:text-white bg-transparent py-3 px-8 font-bold uppercase tracking-widest text-xs hover:bg-gray-900 hover:text-white dark:hover:bg-white dark:hover:text-gray-900 transition-colors rounded-sm">
            <?php esc_html_e( 'Explorar Colección', 'bruiser-tech-lhparfum' ); ?>
        </a>
    </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_mini_cart' ); ?>
