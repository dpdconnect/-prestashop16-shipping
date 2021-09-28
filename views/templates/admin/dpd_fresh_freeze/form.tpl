<div class="row">

    <div class="col-lg-12">
        <form action="{$redirectUrl|escape:'htmlall':'utf-8'}" method="post">
            <input type="hidden" name="hasFreshFreezeData">
            {foreach from=$orderProducts key=orderId item=products}
                <div class="panel">
                    <div class="panel-heading">
                        Order #{$orderId}
                    </div>
                    {foreach from=$products item=product}
                        <div style="display: inline-block; margin: 10px">
                            {assign var="productCover" value=Product::getCover($product.product_id)}
                            {assign var="productReference" value=$product.reference}
                            {if empty($productReference)}
                                {assign var="productReference" value="Not supplied"}
                            {/if}

                            <p>
                                <img alt="product image"
                                     width="200"
                                     height="200"
                                     src="{$product.image_url}">
                            </p>

                            <h3>{$product.product_name}</h3>

                            <p><strong>Reference:</strong> {$productReference}</p>
                            <p><strong>Type:</strong> {$product.dpd_shipping_product|capitalize}</p>
                            <p><strong>Weight:</strong> {$product.weight|string_format:"%.1f"} {$weight_unit}</p>
                            <p><strong>Quantity:</strong> {$product.product_quantity}</p>

                            <h3 style="margin-top: 5px;">Expiration date</h3>
                            <div class="input-group fixed-width-md">
                                <input id="dpd_expiration_date_{$orderId}_{$product.product_id}" name="dpd_expiration_date_{$orderId}_{$product.product_id}" value="{$defaultDate}" class="datepicker" type="text" required/>
                                <div class="input-group-addon">
                                    <i class="icon-calendar-empty"></i>
                                </div>
                            </div>

                            <h3 style="margin-top: 5px;">Carrier description</h3>
                            <textarea name="dpd_carrier_description_{$orderId}_{$product.product_id}" cols="15" rows="5" required>{$product.dpd_carrier_description}</textarea>
                        </div>
                    {/foreach}
                </div>
            {/foreach}

            <button type="submit" name="submit" class="btn btn-default pull-right" style="padding: 10px 30px 10px 30px;">
                <i class="process-icon-save"></i>
                {l s='Save'}
            </button>
        </form>
    </div>
</div>
<script type="text/javascript">
    $('document').ready(function(){
        $('.datepicker').datepicker({
            prevText: '',
            nextText: '',
            dateFormat: 'yy-mm-dd',
            minDate: new Date()
        });
    });
</script>

