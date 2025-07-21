{*
* 2007-2023 WebImpacto
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
* Do not edit or add to this file if you wish to upgrade RSiSistemas to newer
* versions in the future. If you wish to customize RSiSistemas for your
* needs please refer to http://www.catalogo-onlinersi.net for more information.
*
*  @author    RSI Sistemas <demo@demo.coms>
*  @copyright 2007-2023 RSI Sistemas
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of RSiSistemas 
*}
<style type="text/css">
#content.bootstrap button{
    width: 100%;
    padding: 5px;
    margin: 5px 0;
}

</style>
<script type="text/javascript">
	$(document).ready(function() {
		var id_section = {$section_adminpage};
		var section = ["general", "configuracion", "ayuda"];
		var tabs = ["tab1", "tab2", "tab3"];

		switch (id_section) {
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
		$("#general_tab").click(function() {
			loop_section("general", "tab1");
		});
		$("#configuracion_tab").click(function() {
			loop_section("configuracion", "tab2");
		});
		$("#ayuda_tab").click(function() {
			loop_section("ayuda", "tab3");
		});


		function loop_section(contentindex, tab) {
			var index;
			for (index = 0; index < section.length; ++index) {
				console.log(section[index] + "==" + contentindex);

				if (section[index] == contentindex) {
					$("#" + contentindex).addClass("active");
				} else {
					$("#" + section[index]).removeClass("active");
				}
			}

			var indextab;
			for (indextab = 0; indextab < tabs.length; ++indextab) {
				console.log(tabs[indextab] + "==" + tab);

				if (tabs[indextab] == tab) {
					console.log("#" + tab);

					$("#" + tab).addClass("active");
				} else {
					$("#" + tabs[indextab]).removeClass("active");
				}
			}
		}
	});
</script>


<ul class="nav nav-tabs" id="wimhide">
	<li id="tab1" class="active">
		<a href="#" id="general_tab">
			<i class="icon-home"></i>
			{l s='Dashboard' mod='rsi_generator'}
		</a>
	</li>
	<li id="tab2">
		<a href="#" id="configuracion_tab">
			<i class="icon-database"></i>
			{l s='Configuration' mod='rsi_generator'}
		</a>
	</li>
	<li id="tab3">
		<a href="#" id="ayuda_tab">
			<i class="icon-cogs"></i>
			{l s='Help' mod='rsi_generator'}
		</a>
	</li>


</ul>
<div class="tab-content panel">
	<div class="tab-pane active" id="general">
		<h1><i class="icon icon-puzzle-piece"></i> {l s='RSI Module Generator' mod='rsi_generator'}</h1>

		<div class="alert alert-success" role="alert">
			{l s='Generate custom modules. After save in the Configuration section, you can download the zip, or load an already generated module.' mod='rsi_generator'}
		</div>

		<p> {l s='The module save all modules generated in the GENERATED folder  of this module.' mod='rsi_generator'}
		</p><br />
		<p> {l s='Check the help section to see how to use the module.' mod='rsi_generator'} </p><br />
		<p>
			<center><img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/readme.png"
					style="  width: 31px;margin: 5px;" /><a href="{$module_dir|escape:'htmlall':'UTF-8'}readme.pdf"
					target="_blank">README</a> /
				<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/readme.png"
					style="  width: 31px;margin: 5px;" /><a
					href="{$module_dir|escape:'htmlall':'UTF-8'}termsandconditions.pdf" target="_blank">TERMS</a>
			</center>
		</p>

	</div>
	<div class="tab-pane" id="configuracion">
	
		

		{$renderForm}
		<table class="table">
			<h2 class='h2'>{l s='Already generated modules:' mod='rsi_generator'}</h2>
			<thead style="font-size:11px">
				<tr>
					<th scope="col"> ACTION</th>
					{foreach $saved as $save}
						{foreach $save as $k=>$v}
							{if $k != "RSI_GENERATOR_COPY" && $k != "RSI_GENERATOR_HOWTO"  && $k != "RSI_GENERATOR_COPYRIGHT"  && $k != "RSI_GENERATOR_ICON" && $k != "RSI_GENERATOR_BOJS"	&& $k != "RSI_GENERATOR_BOCSS"	&& $k != "RSI_GENERATOR_FOJS"	&& $k != "RSI_GENERATOR_FOCSS"}
								{if $save@index eq 0}
									<th scope="col"> {$k|replace:"RSI_GENERATOR_":""}</th>

								{/if}
							{/if}
						{/foreach}
					{/foreach}

				</tr>
			</thead>
			<tbody>
				<tr>

					{foreach $saved as $save}
						<td>
							<form id="restore" action="{$form}" method="post">
								{foreach $save as $k=>$v}
									{if $k == "RSI_GENERATOR_MODULENAME"}
										<input type='hidden' name="restorefields" value="{$v}">
									{/if}
									{if $k == "RSI_GENERATOR_PREFIX"}
										<input type='hidden' name="restorefieldspre" value="{$v}">
									{/if}
								{/foreach}
								<button type="submit" name="restore" class="btn btn-primary btn-xs"><i
										class="icon-upload"></i> {l s='Load' mod='rsi_generator'}</button>
								<button type="submit" name="deletem" class="btn btn-danger btn-xs"><i
										class="icon-trash"></i> {l s='Delete' mod='rsi_generator'}</button>
								<button type="submit" name="downloadm" class="btn btn-success btn-xs"><i
										class="icon-download"></i> {l s='ZIP' mod='rsi_generator'}</button>
							</form>
						</td>

						{foreach $save as $k=>$v}

							{if $k != "RSI_GENERATOR_COPY" && $k != "RSI_GENERATOR_HOWTO"  && $k != "RSI_GENERATOR_COPYRIGHT" && $k != "RSI_GENERATOR_ICON"  && $k != "RSI_GENERATOR_BOJS"	&& $k != "RSI_GENERATOR_BOCSS"	&& $k != "RSI_GENERATOR_FOJS"	&& $k != "RSI_GENERATOR_FOCSS"}
								<td>
									{if is_array($v)}
										{foreach $v as $a}
											{$a},
										{/foreach}
									{else}
										{$v}
									{/if}
								</td>
							{/if}
						{/foreach}

					</tr>
				</tbody>
			{/foreach}
		</table>
		



		</div>
		<div class="tab-pane" id="ayuda">
			<p> {l s='First, choose a module name (dont use wim, as we already add the prefix), select a description and a title for the module' mod='rsi_generator'}
			</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h1.jpg" width="55%"><br />
			<p> {l s='Choose the hooks that you need in your module.' mod='rsi_generator'}</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h2.jpg" width="55%"><br />
			<p> {l s='The class section, adds a specific Select with the options for the options you choose. If you choose carrier, you get a select with all the carriers, same with manufacturers, etc.' mod='rsi_generator'}
			</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h3.jpg" width="55%"><br />
			<p> {l s='You get something like this select if you choose Country class.' mod='rsi_generator'}</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h4.jpg" width="55%"><br />
			<p> {l s='The rest of items are quite descriptive, like text fields, color fields, etc. Remember, if you need 2 text fields, put the name with comma sepparated, and the module generated 2 text fields (you can do this for any field).' mod='rsi_generator'}
			</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h5.jpg" width="48%"><br />
			<p> {l s='The module also generates the javascript and css files if you select the options (FO and BO)' mod='rsi_generator'}
			</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h6.jpg"><br />
			<p> {l s='The last options are only to add a descriptive help, a copyright and a liscence (used in all files)' mod='rsi_generator'}
			</p>
			<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/h7.jpg" width="55%"><br />
			{if $jira}
				<div class="alert alert-info" role="alert">
					{l s='If you have any question or want to made updates in the module, you can write in the related task in jira:' mod='rsi_generator'}<br />
					<hr>
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/jira.png">
					<a href="{$jira}" target="_blank">{$jira}</a>
				</div>
			{/if}

			{if $employee and $git}
				<div class="alert alert-warning" role="alert">
					{l s='The related repository in git to this task is:' mod='rsi_generator'}<br />
					<hr>
					<img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/git.png"><i class="bi bi-github"></i>
					<a href="{$git}" target="_blank">{$git}</a>
				</div>
			{/if}
		</div>
</div>