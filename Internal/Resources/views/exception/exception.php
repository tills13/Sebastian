<?php
    $preMessage = "";

    foreach ($exception->getTrace() as $index => $trace) {
        $preMessage .= "#{$index} " . ($trace['file'] ?? "(No File)") . " (" . ($trace['line'] ?? "--") . ")\n";
    }
?>

<?=$this->extend('master')?>

<?=$this->block('title', get_class($exception))?>

<?=$this->block('body')?>
    <div>
        <h2><?=$exception->getMessage()?><br/><small><?=get_class($exception)?></small></h2>
        <hr/>
        <pre><?=$preMessage?></pre>
    </div>
<?=$this->endBlock()?>