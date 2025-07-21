{*
* 2007-2023 RSI Sistemas
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@WebImpacto.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade WebImpacto to newer
* versions in the future. If you wish to customize WebImpacto for your
* needs please refer to http://www.WebImpacto.com for more information.
*
*  @author    RSI Sistemas <demo@demo.coms>
*  @copyright 2007-2023 RSI
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of WebImpacto 
*}

<script type="text/javascript">
	$(document).ready(function(){
		var id_section = {$section_adminpage};
		var section = ["general", "configuracion", "ayuda"];
		var tabs = ["tab1", "tab2", "tab3"];

		switch(id_section) {
			case 2:
		        sectindex = "configuracion";
		        tabindex = "tab2";
		        break;
		    case 3:
		        sectindex = "ayuda";
		        tabindex = "tab3";
		        break;
		    case 1:
		    	sectindex = "general";
		        tabindex = "tab1";
		        break;
		    default:
		    	sectindex = "general";
		        tabindex = "tab1";
		        break;
		}

		loop_section(sectindex, tabindex);

		//click tab event
		$("#general_tab").click(function(){
			loop_section("general", "tab1");
		});
		$("#configuracion_tab").click(function(){
			loop_section("configuracion", "tab2");
		});
		$("#ayuda_tab").click(function(){
			loop_section("ayuda", "tab3");
		});


		function loop_section(contentindex, tab){
			var index;
			for (index = 0; index < section.length; ++index) {
			    console.log(section[index]+"=="+contentindex);

			    if(section[index] == contentindex){
			    	$("#"+contentindex).addClass("active");
			    }else{
			    	$("#"+section[index]).removeClass("active");
			    }
			}

			var indextab;
			for (indextab = 0; indextab < tabs.length; ++indextab) {
			    console.log(tabs[indextab]+"=="+tab);

			    if(tabs[indextab] == tab){
			    	console.log("#"+tab);

			    	$("#"+tab).addClass("active");
			    }else{
			    	$("#"+tabs[indextab]).removeClass("active");
			    }
			}
		}
	});	
</script>


<ul class="nav nav-tabs" id="wimhide">				
	<li id="tab1" class="active">
		<a href="#" id="general_tab">
			<i class="icon-home"></i>
			  {l s='Dashboard' mod='wim_generator'}
		</a>
	</li>
	<li id="tab2">
		<a href="#" id="configuracion_tab">
			<i class="icon-database"></i>
			  {l s='Configuration' mod='wim_generator'}
		</a>
	</li>
	<li id="tab3">
		<a href="#" id="ayuda_tab">
			<i class="icon-cogs"></i>
			  {l s='Help' mod='wim_generator'}
		</a>
	</li>


</ul>
<div class="tab-content panel">	
	<div class="tab-pane active" id="general">
		<h1><i class="icon icon-gear"></i> {l s='Wim Hide Products' mod='wim_generator'}</h1>

		<div class="alert alert-danger" role="alert">
  {l s='Install and configure this module in each store, dont install in all stores.' mod='wim_generator'}
</div>
		<div class="alert alert-warning" role="alert">
  {l s='You can check after save the configuration, which products change to hidden before run the cron proccess, just go to Advanced Parameters -> SQl queries and search the query WIM_HIDDEN.' mod='wim_generator'}
</div>
		<p>
			<strong>{l s='Instructions' mod='wim_generator'}</strong><br />
			{l s='The module change the status of products to HIDDEN when stock in selected wharehouses reach Zero. Configure the module with the wharehouse IDS and excluded categories' mod='wim_generator'}<br />
		</p>
		<strong>{l s='Cron URL' mod='wim_generator'}</strong><br />
		{l s='Set this cron URL in a cron job to run the disable/enable products' mod='wim_generator'}<br />
		<a href="{$module_dir|escape:'htmlall':'UTF-8'}wim_generator-cron.php?token={$token|escape:'htmlall':'UTF-8'}&id_shop={$id_shop}" target="_blank">{$module_dir|escape:'htmlall':'UTF-8'}wim_generator-cron.php?token={$token|escape:'htmlall':'UTF-8'}&id_shop={$id_shop}</a><br/><br/>
		{if $fileresult}
		<div class="alert alert-success" role="alert">
        {$fileresult}
		</div>
		{/if}
	</div>
	<div class="tab-pane" id="configuracion">
		{$renderForm}
	</div>
	<div class="tab-pane" id="ayuda">
	{if $jira}
		<div class="alert alert-info" role="alert">
       {l s='If you have any question or want to made updates in the module, you can write in the related task in jira:' mod='wim_generator'}<br/>
	    <hr>
	   <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/jira.png">
	   <a href="{$jira}" target="_blank">{$jira}</a>
		</div>
	{/if}
    {if $wimemployee and $git}
	<div class="alert alert-warning" role="alert">
        {l s='The related repository in git to this task is:' mod='wim_generator'}<br/>
		 <hr>
		<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/git.png"><i class="bi bi-github"></i>	
		<a href="{$git}" target="_blank">{$git}</a></div>
	{/if}

	{l s='The module allows you to hide the products from the GUADALAJARA AND MALAGA warehouses. For this, a few rules are followed:' mod='wim_generator'}
	<ul>
<li>{l s='1-The stock must be equal to or less than zero in both wharehouses' mod='wim_generator'}</li>
<li>{l s='2-Not be in the categories selected in the module' mod='wim_generator'}</li>
<li>{l s='3-They must be active products' mod='wim_generator'}</li>
<li>{l s='4- Belong to the ID SHOP where the module is configured' mod='wim_generator'}</li>
</ul>
		<strong> {l s='2022, developed by ' mod='wim_generator'} <a href="http://demo/" target="_blank">RSI</a></strong>
	</div>	
</div>



