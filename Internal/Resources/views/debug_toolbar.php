<style>
    body {
        margin-bottom: 45px;
    }

    .debug-toolbar {
        position: fixed;
        bottom: 0px;
        width: 100%;
        height: 45px;
        border-top: 1px solid lightgrey;
        background: rgba(255,255,255,0.6);
    }

    .debug-toolbar > * {
        display: inline-block;
        line-height: 45px;
        padding: 0px 10px;
    }

    .debug-toolbar .branding {
        color: #2b303b;
        font-weight: bold;
        font-family: 'Open Sans', Helvetica;
    }

    .debug-toolbar > .popup {
        position: relative;
        display: inline-block;
    }

    .debug-toolbar > .popup > .section {
        display: inline-block;
        padding: 0px 10px;
        color: #2b303b;
        cursor: pointer;
    }

    .debug-toolbar > .popup:hover .section {
        color: darkgrey;
    }

    .debug-toolbar > .popup > .body {
        display: none;
        position: absolute;
        bottom: 0%;
        min-width: 300px;
        padding: 10px;
        background: rgba(255,255,255,0.90);
        border: 1px solid lightgrey;
        border-bottom: none;
        border-top-left-radius: 2px;
        border-top-right-radius: 2px;
    }

    .debug-toolbar > .popup:hover .body {
        display: block;
        bottom: 100%;
    }

    .debug-toolbar > .popup > .body h4:first-child {
        margin-top: 0px;
    }
</style>

<div class="debug-toolbar">
    <a class="branding" href="#">Sebastian</a>

    <div class="popup">
        <div class="section">Request</div>
        <div class="body">
            <h4>Request</h4>
            <table class="table table-striped">
                <tr>
                    <td>route</td>
                    <td><?=$request->route()?></td>
                </tr>
            </table>
        </div>
    </div>

    <?php 
        $cm = $application->getCacheManager();
        $drivers = $cm->getDrivers();
        //$cacheInfo = $driver->getInfo();
        //
    ?>

    <div class="popup">
        <div class="section">Cache</div>
        <div class="body">
            <?php 
                foreach ($drivers as $driver) {
                    $cacheInfo = $driver->getInfo();
                    $cachedItems = array_slice($cacheInfo['cache_list'] ?? [], 0, 5);
            ?>
                <h4>Cache <small><?=$driver->getName()?></small></h4>
                <table class="table table-striped">
                    <?php foreach($cachedItems as $key => $item) { ?>
                        <tr data-key="<?=$item['info']?>">
                            <td data-sortable-key="<?=$item['info']?>">
                                <a class="u" href="javascript:;" data-entity-id="<?=$item['info']?>"><?=$item['info']?></a>
                            </td>
                            <td>
                                <a href="javascript:;" class="fa fa-times" onclick="admin.invalidateCache(this)"></a>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </div>
    </div>

    
    <div class="popup">
        <div class="section">User</div>
        <div class="body">
            <?php if (($user = $session->getUser()) !== null) { ?>
                <h4>Current User</h4>
                <table class="table table-striped">
                    <tr>
                        <td>username</td>
                        <td><?=$user->getUsername()?></td>
                    </tr>
                    <tr>
                        <td>admin</td>
                        <td><?=$user->getIsAdmin() ? 'yes' : 'no'?></td>
                    </tr>
                </table>
            </div>
        <?php } else { ?>
            <p>Not signed in...</p>
        <?php } ?>
    </div>
</div>