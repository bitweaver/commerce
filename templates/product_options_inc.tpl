{if $productOptions}
	<div class="product options">
		{foreach from=$productOptions key=optionsId item=opts}
			<div class="form-group">
				{formlabel label=$opts.name}

				{if $opts.comment}{formhelp note=$opts.comment}{/if}

				{forminput}
					{$opts.menu}
					{if $opts.attributes_image}
						<div class="thumbnail">{$opts.attributes_image}</div>
					{/if}
				{/forminput}
			</div>

			{if $productSettings.show_onetime_charges_description == 'true'}
				<div class="form-group">
					{$smarty.const.TEXT_ONETIME_CHARGE_SYMBOL}{$smarty.const.TEXT_ONETIME_CHARGE_DESCRIPTION}
				</div>
			{/if}

			{if $productSettings.show_attributes_qty_prices_description == 'true'}
				<img src="{$smarty.const.DIR_WS_TEMPLATE_ICONS}icon_status_green.gif" alt="{$smarty.const.TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK}" />&nbsp;<a href="javascript:popupWindowPrice('{$smarty.const.BITCOMMERCE_PKG_URI}?main_page={$smart.const.FILENAME_POPUP_ATTRIBUTES_QTY_PRICES}&products_id={$gBitProduct->mProductsId}&products_tax_class_id={$gBitProduct->mInfo.products_tax_class_id}')">{$smarty.const.TEXT_ATTRIBUTES_QTY_PRICE_HELP_LINK}</a>
			{/if}
		{/foreach}
	</div>
{/if}
