<?php
if(!defined("opoiLoaded")) die('Incorrect or unknown use of application');
$authMgr->requireSuperAdmin();

$events = $driver->getAllEvents();
?>

<script type="text/javascript">
$(document).ready(function() {
$('#events').dataTable( {
	"bPaginate": false,
	"bLengthChange": false,
	"bFilter": false,
	"bSort": false,
	"bInfo": false,
	"bAutoWidth": true,
	"aoColumns": [
            {sName: "id" },
            {sName: "name" },
            {sName: "public" },
            {sName: "starttime"},
            {sName: "endtime" },
            {sName: "submit" }
      ]
	})
	.makeEditable({
	    sUpdateURL: "<?= BASE_URL . 'ajax/events.ajax.php' ?>",
	    sDeleteURL: "<?= BASE_URL . 'ajax/events.ajax.php' ?>"
	})
});
</script>

<h1>Events</h1>

<button id="btnDeleteRow">Verwijder event</button>

<form action="<?= BASE_URL . 'ajax/events.ajax.php' ?>" method="POST">
<table id="events">
    <thead>
        <tr>
            <th>ID</th>
            <th>name</th>
            <th>public</th>
            <th>starttime</th>
            <th>endtime</th>
            <th></th>
        </tr>
    </thead>
 	<tfoot>
        <tr id="addEvent">
            <td></td>
            <td><input type="text" name="name" /></td>
            <td>
                <select name="public">
                  <option value="t">Yes</option>
                  <option value="f">No</option>
                </select>
            </td>
            <td><input type="text" name="starttime" /></td>
            <td><input type="text" name="endtime" /></td>
            <td>
                <input type="submit" value="Voeg toe" class="button" />
            </td>
        </tr>
    </tfoot>
    <tbody>
        <?php foreach ($events as $event) { ?>
        <tr id="<?= $event->getId() ?>">
            <td class="read_only"><?= $event->getId() ?></td>
            <td><?= $event->getName() ?></td>
            <td><?= $event->isPublic() ?></td>
            <td><?= $event->getStarttime() ?></td>
            <td><?= $event->getEndtime() ?></td>
            <td class="read_only"></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</form>