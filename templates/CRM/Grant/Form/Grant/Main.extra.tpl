{if $amountField}
    {literal}
	<script type='text/javascript'>
   		 cj("#amount_total").keydown(function (e) {
        	 // Allow: backspace, delete, tab, escape, enter and .
        	    if (cj.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             	    // Allow: Ctrl+A
            	       (e.keyCode == 65 && e.ctrlKey === true) || 
             	       // Allow: home, end, left, right
            	       (e.keyCode >= 35 && e.keyCode <= 39)) {
                       // let it happen, don't do anything
                       return;
        	    }
        	     // Ensure that it is a number and stop the keypress
        	    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            	       e.preventDefault();
        	     }
   		 });
	</script>
    {/literal}
{/if}
{if $fileFields}
    {literal}
	<script type='text/javascript'>
    {/literal}
    {foreach from=$fileFields item=values key=id}
	{literal}
		var id = {/literal}'{$id}'{literal};
		var displayURL = {/literal}'{$values.displayURL}'{literal};
		var fileURL = {/literal}'{$values.fileURL}'{literal};
		var fileName = {/literal}'{$values.fileName}'{literal};
		var fid = {/literal}'{$values.fid}'{literal};
		if (displayURL) {
		   cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+displayURL+' class=crm-image-popup><img src='+displayURL+' height="100" width="100"></a><a href='+fileURL+'&fid='+fid+'&action=delete onclick="if (confirm(\'Are you sure you want to delete attached file?\')) this.href+=\'&confirmed=1\';else return false;"><span class="icon red-icon delete-icon" style="margin:0px 0px -5px 20px" title="Delete this file"></span></a></div>');
                }
		else {
		   cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+fileURL+'>'+fileName+'</a><a href='+fileURL+'&fid='+fid+'&action=delete onclick="if (confirm(\'Are you sure you want to delete attached file?\')) this.href+=\'&confirmed=1\';else return false;"><span class="icon red-icon delete-icon" style="margin:0px 0px -5px 20px" title="Delete this file"></span></a></div>');
                }
        {/literal}
    {/foreach}
    {literal}
	</script>
    {/literal}
{/if}
{if $oFileFields}
    {literal}
	<script type='text/javascript'>
    {/literal}
    {foreach from=$oFileFields item=values key=id}
	{literal}
		var id = {/literal}'{$id}'{literal};
		var displayURL = {/literal}'{$values.displayURL}'{literal};
		var fileURL = {/literal}'{$values.fileURL}'{literal};
		var fileName = {/literal}'{$values.fileName}'{literal};
		var fid = {/literal}'{$values.fid}'{literal};
		if (displayURL) {
		   cj('.'+id+'-section').append('<div class=label>Attached File:</div><div class=content><a href='+displayURL+' class=crm-image-popup><img src='+displayURL+' height="100" width="100"></a><a href='+fileURL+'&fid='+fid+'&action=delete onclick="if (confirm(\'Are you sure you want to delete attached file?\')) this.href+=\'&confirmed=1\';else return false;"><span class="icon red-icon delete-icon" style="margin:0px 0px -5px 20px" title="Delete this file"></span></a></div>');
                }
		else {
		   cj('.'+id+'-section').append('<div class=label>Attached File:</div><div class=content><a href='+fileURL+'>'+fileName+'</a><a href='+fileURL+'&fid='+fid+'&action=delete onclick="if (confirm(\'Are you sure you want to delete attached file?\')) this.href+=\'&confirmed=1\';else return false;"><span class="icon red-icon delete-icon" style="margin:0px 0px -5px 20px" title="Delete this file"></span></a></div>');
                }
        {/literal}
    {/foreach}
    {literal}
	</script>
    {/literal}
{/if}
