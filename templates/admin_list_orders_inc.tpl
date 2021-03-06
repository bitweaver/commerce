{if $searchScopes}
{form class="form-inline" }
{html_options class="form-control" name="search_scope" options=$searchScopes selected=$smarty.session.search_scope|default:'all'}
: <input type="text" class="form-control" name="search" value="{$smarty.session.search|default:$smarty.request.search}"/>
<select class="form-control" name="orders_status_comparison" class="input-small" >
	<option value="">{tr}Exactly{/tr}</option>
	<option value=">=" {if $smarty.session.orders_status_comparison == '>='}selected="selected"{/if}>{tr}At Least{/tr}</option>
	<option value="<=" {if $smarty.session.orders_status_comparison == '<='}selected="selected"{/if}>{tr}At Most{/tr}</option>
</select>

{html_options class="form-control" name="orders_status_id" options=$commerceStatuses selected=$smarty.session.orders_status_id|default:'all'}

<input class="btn btn-default btn-sm" type="submit" value="Go" name="list_filter"/>
{/form}
{/if}

<table class="table data" style="table-layout:fixed">
{assign var=grossTotal value=0}
{assign var=wholesaleProfitTotal value=0}
{assign var=distributorIncomeTotal value=0}
{assign var=cogsTotal value=0}
{foreach from=$listOrders key=orderId item=order}
	{assign var=grossTotal value=$grossTotal+$order.order_total}
	<tr>
		<td style="width:10em;text-align:left">{$order.purchase_time}</td>
		<td colspan="5"><a href="{$smarty.const.BITCOMMERCE_PKG_URL}admin/orders.php?oID={$orderId}" class="contentlink">{$orderId} - {BitUser::getDisplayNameFromHash($order)}</a><div class="date pull-right text-right">{$order.delivery_city}, {$order.delivery_state}{if $order.shipping_method_code}<br><span class="small">{$order.shipping_method_code}</span>{/if}</div></td>
		<td class="text-right">{$order.orders_status_name}</td>
		<td class="text-right">{$gCommerceCurrencies->format($order.order_total, TRUE, $order.currency|default:DEFAULT_CURRENCY, $order.currency_value|default:1)}</td>
	</tr>
	{if $order.comments && $order.comments!='Credit Card processed'}
	<tr class="comments">
		<td colspan="8"><span class="badge">{$order.comments_time}</span> {$order.comments}</td>
	</tr>
	{/if}
	{if $order.products}
	{foreach from=$order.products item=product key=ordersProductsId name="orderproducts"}
	<tr>
		<td colspan="4">
			<div class="row">
				<div class="col-xs-2">
					<img src="{CommerceProduct::getImageUrlFromHash($product)}" class="img-responsive"/>
				</div>
				<div class="col-xs-8">		
					#{$smarty.foreach.orderproducts.iteration} - 
					<a href="{$gBitProduct->getDisplayUrlFromHash($product)}">{$product.products_name}</a>
					{if $product.attributes}
						<ul>
							{foreach from=$product.attributes item=attrName key=optionId}
								<li>{$attrName}</li>
							{/foreach}
						</ul>
					{/if}
				</div>
			</div>
		</td>
		<td class="text-right">
			{assign var=quantityTotal value=$quantityTotal+$product.products_quantity}
			{$product.products_quantity} x 
			{assign var=finalIncome value=$product.products_quantity*$product.final_price}
			{assign var=finalTotal value=$finalTotal+$finalIncome}
			${$product.final_price} {if $product.products_quantity>1} : ${$finalIncome} {/if} = 
		</td>
		<td class="text-right">
			{math equation="n*(x-y)" assign=wholesaleProfit n=$product.products_quantity x=$product.final_price|default:0 y=$product.products_wholesale|default:0}
			{assign var=wholesaleProfitTotal value=$wholesaleProfitTotal+$wholesaleProfit}
			+ {$wholesaleProfit|number_format:2}
		</td>
		<td class="text-right">
			{if $gBitUser->hasPermission('p_admin')}
			{math equation="n*(x-y)" assign=distributorIncome n=$product.products_quantity x=$product.products_wholesale|default:0 y=$product.products_cogs|default:0}
			{assign var=distributorIncomeTotal value=$distributorIncomeTotal+$distributorIncome}
			+ [{$distributorIncome|number_format:2}]
			{/if}
		</td>
		<td class="text-right">
			{if $gBitUser->hasPermission('p_admin')}
			{math equation="n*(x)" assign=cogs n=$product.products_quantity x=$product.products_cogs|default:0}
			{assign var=cogsTotal value=$cogsTotal+$cogs}
			+ ({$cogs|number_format:2})
			{/if}
		</td>
		{if $gBitUser->hasPermission('p_admin')}
			{math equation="round( s-x-y-z, 4)" assign=auditValue s=$finalIncome x=$wholesaleProfit|default:0 y=$distributorIncome z=$cogs}
			{if $auditValue != 0}
			<td class="error">
				Audit error {$auditValue}
			</td>
			{/if}
		{/if}
	</tr>
	{/foreach}
	{/if}
{/foreach}
<tr>
	{if $wholesaleProfitTotal}
	<th class="item text-right" colspan="3"></th>
	<th class="item text-right">{tr}Total{/tr}: {$quantityTotal}</th>
	<th class="item text-right">${$finalTotal|round:2}</th>
	<th class="item text-right">${$wholesaleProfitTotal|round:2}</th>
	<th class="item text-right">{if $gBitUser->hasPermission('p_admin')}${$distributorIncomeTotal|round:2}{/if}</th>
	<th class="item text-right">{if $gBitUser->hasPermission('p_admin')}${$cogsTotal|round:2}{/if}</th>
	{else}
	<th class="item text-right" colspan="8">{tr}Total{/tr}: ${$grossTotal|round:2}</th>
	{/if}
</tr>

</table>

