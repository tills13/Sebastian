<script type="text/javascript" src="<?=$router->generateUrl("_internal:javascript", ['filename' => 'router.js'])?>"></script>
<script type="text/javascript" src="<?=$router->generateUrl("_internal:javascript", ['filename' => 'sebastian.js'])?>"></script>
<script>
    sebastian.registerRoutes(<?=json_encode($router->getRoutes())?>);
</script>