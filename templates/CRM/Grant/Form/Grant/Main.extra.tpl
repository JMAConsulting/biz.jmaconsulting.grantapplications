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
		   cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+displayURL+' class=crm-image-popup><img src='+displayURL+' height="100" width="100"></a></div>');
                }
		else {
		   cj('#editrow-'+id).append('<div class=label>Attached File:</div><div class=content><a href='+fileURL+'>'+fileName+'</a></div>');
                }
        {/literal}
    {/foreach}
    {literal}
	</script>
    {/literal}
{/if}
