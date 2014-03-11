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