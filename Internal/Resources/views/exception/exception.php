<?php
    $preMessage = "";

    foreach ($exception->getTrace() as $index => $trace) {
        $preMessage .= "#{$index} " . ($trace['file'] ?? "(No File)") . " (" . ($trace['line'] ?? "--") . ")\n";
    }
?>

<?=$this->extend($errorTemplate, $errorTemplate !== null)?>

<?=$this->block('title', get_class($exception))?>

<?=$this->block('body')?>
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-6">
                <h2><?=$exception->getMessage()?><br/><small><?=get_class($exception)?></small></h2>
                <hr/>
                <pre><?=$preMessage?></pre>
            </div>
        </div>
    </div>
<?=$this->endBlock()?>