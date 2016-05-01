<?=$this->extend('master')?>

<?=$this->block('body')?>
<div>
	<h2>Something Went Wrong... <small><?=get_class($exception)?></small></h2>
	<b><?=$exception->getMessage()?></b>
	<pre><?php foreach ($exception->getTrace() as $index => $trace) { 
			echo("#{$index} {$trace['file']}({$trace['line']})\n");
		} ?></pre>
</div>
<?=$this->endBlock()?>