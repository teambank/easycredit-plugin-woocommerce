<tr>
    <th colspan="2">
        <div class="easycredit-marketing">
            <h2><?php _e('Marketing', 'wc-easycredit'); ?></h2>

            <nav class="easycredit-marketing__tabs">
                <a class="easycredit-marketing__tab active" data-target="intro"><?php _e('Overview', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="express"><?php _e('Express Button', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="widget"><?php _e('Widget', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="modal"><?php _e('Modal', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="card"><?php _e('Card', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="flashbox"><?php _e('Flashbox', 'wc-easycredit'); ?></a>
                <a class="easycredit-marketing__tab" data-target="bar"><?php _e('Bar', 'wc-easycredit'); ?></a>
            </nav>

            <div class="easycredit-marketing__tab-content intro active" data-tab="intro">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('We provide you with a range of marketing components that you can easily activate in your online shop. In this way, you achieve an optimal presentation of financing via easyCredit-Ratenkauf, for higher sales and satisfied customers.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Useful links', 'wc-easycredit'); ?></h3>
                        <ul>
                            <li><a href="https://netzkollektiv.com/docs/easycredit-ratenkauf-components/" target="_blank"><?php _e('Documentation', 'wc-easycredit'); ?></a></li>
                            <li><a href="https://easycredit-demo.netzkollektiv.com/" target="_blank"><?php _e('Demo shop with marketing components', 'wc-easycredit'); ?></a></li>
                            <li><a href="https://www.easycredit-ratenkauf.de/marketingmaterial-schulung/" target="_blank"><?php _e('Marketing portal', 'wc-easycredit'); ?></a></li>
                        </ul>
                    </div>

                    <div class="easycredit-marketing__image">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-modal.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content express" data-tab="express">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('The Express button can be displayed on the product detail page and in the shopping cart. It allows customers to buy the product or shopping cart without detours through the ordering process.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-express_checkout">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-express-button.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content widget" data-tab="widget">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('The widget component is shown on the product detail page and in the shopping cart. It calculates the lowest possible rate with which a product with a certain price can be financed. Clicking on \"more info\" opens the rate calculator with further details on financing. If the product price is outside the possible financing amounts, the widget indicates the minimum or maximum possible financing amount.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-widget">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-widget.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content modal" data-tab="modal">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('The modal component can be used to actively draw the customer\'s attention to the instalment purchase in the shop. A time-controlled display of the modal is possible. If the customer closes the modal, the component remembers this and prevents it from being displayed again.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-modal">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-modal.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content eccard" data-tab="card">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('The card component can be used to replace a product within the product list with a banner to advertise the hire purchase. The banner shows a standard image that can be overwritten. This allows you to use an image that matches your product offering.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-card">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-card.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content flashbox" data-tab="flashbox">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('This marketing component allows a banner to be displayed at the bottom of the screen to advertise hire purchase. The banner shows a standard image that can be overwritten. This allows you to use an image that matches your product offer.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-flashbox">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-flashbox.png" alt="Modal"></div>
                    </div>
                </div>
            </div>

            <div class="easycredit-marketing__tab-content ecbar" data-tab="bar">
                <div class="easycredit-marketing__grid">
                    <div class="easycredit-marketing__content">
                        <p>
                            <?php _e('The bar component can be displayed at the top of the screen and promotes the hire purchase with short slogans. The slogans are predefined and cannot be customised.', 'wc-easycredit'); ?>
                        </p>

                        <h3><?php _e('Settings', 'wc-easycredit'); ?></h3>
                        <div class="easycredit-marketing__content__settings settings-bar">[...]</div>
                    </div>

                    <div class="easycredit-marketing__image bg">
                        <div class="image"><img src="<?php echo plugin_dir_url(__FILE__); ?>../assets/img/easycredit-marketing-bar.png" alt="Modal"></div>
                    </div>
                </div>
            </div>
        </div>
    </th>
</tr>
