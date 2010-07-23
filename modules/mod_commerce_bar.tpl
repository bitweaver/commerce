<div class="commercebar">

<span class="floaticon">
<strong>{tr}Your Cart{/tr}:</strong>
{if $sessionCart}
{if count($gCommerceCurrencies->currencies) > 1}
<a href="{$smarty.const.BITCOMMERCE_PKG_URL}?main_page=shopping_cart">{$sessionCart->count_contents()} {if $sessionCart->count_contents()==1}{tr}Item{/tr}{else}{tr}Items{/tr}{/if}</a>
( {$gCommerceCurrencies->format($sessionCart->show_total())} ) 
	<a href="" onclick="BitBase.showById('currencychooser');return false;">{$smarty.session.currency|default:$smarty.const.DEFAULT_CURRENCY} &raquo; &euro;,&yen;</a>
	<form action="{$smarty.server.REQUEST_URI}" id="currencychooser" style="display:none">
	<select name="currency" onchange="this.form.submit()">
		<option value="">Change Currency...</option>
		{foreach from=$gCommerceCurrencies->currencies item=currencyHash key=currencyCode}
			<option value="{$currencyCode}" {if $smarty.session.currency==$currencyCode}selected="selected"{/if}>{$currencyHash.title|escape:html}</option>
		{/foreach}
	</select>
	</form>
{/if}
{else}
{tr}<a href="{$smarty.const.BITCOMMERCE_PKG_URL}?main_page=shopping_cart">Empty</a>{/tr}
{/if}

</span>
<div class="clear"></div>
</div>
