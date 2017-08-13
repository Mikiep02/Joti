<?php
require_once '../init.php';
$authMgr->requireAdmin();
require_once BASE_DIR . 'header.php';

$users = array();
if ($authMgr->isSuperAdmin()) {
    $users = $driver->getAllUsersSuperAdmin();
} else {
    $users = $driver->getAllUsers();
}

?>

<script type="text/javascript">
$(document).ready(function() {
$('#users').dataTable( {
	"bPaginate": false,
	"bLengthChange": false,
	"bFilter": false,
	"bSort": false,
	"bInfo": false,
	"bAutoWidth": true,
	"aoColumns": [
            {sName: "id" },
            {sName: "username" },
            {sName: "displayname" },
            {sName: "pw_hash", sType: "password" },
            {sName: "groups" },
            {sName: "organisation" },
            {sName: "submit" }
      ]
	})
	.makeEditable({
	    sUpdateURL: "<?php echo BASE_URL . 'ajax/users.ajax.php'; ?>",
	    sDeleteURL: "<?php echo BASE_URL . 'ajax/users.ajax.php'; ?>"
	})
});
</script>

<h1>Users</h1>

<button id="btnDeleteRow">Verwijder user</button>

<form action="<?php echo BASE_URL . 'ajax/users.ajax.php'; ?>" method="POST">
<table id="users">
    <thead>
        <tr>
            <th>ID</th>
            <th>username</th>
            <th>displayname</th>
            <th>password</th>
            <th>groups</th>
            <th>organisation</th>
            <th></th>
        </tr>
    </thead>
 	<tfoot>
            <tr id="addUser">
                <td></td>
                <td><input type="text" name="username" /></td>
                <td><input type="text" name="displayname" /></td>
                <td><input type="password" name="pw_hash" /></td>
                <td>
                    <input type="checkbox" name="groups[]" value="1">Admin<br />
                    <input type="checkbox" name="groups[]" value="2" checked="checked">Users
                </td>
                <?php if($authMgr->isSuperAdmin()) { ?>
                <td>
                    <select name="organisation">
                    <?php
                    $orgs = $driver->getAllOrganisations();
                    foreach ($orgs as $org) { ?>
                        <option value="<?= $org->getId() ?>"><?= $org->getName() ?></option>
                    <?php } ?>
                    </select>
                </td>
                <?php } else { ?>
                <td>
                    <input type="hidden" name="organisation" value="<?= $authMgr->getMyOrganisationId() ?>">
                    <?= $driver->getOrganisationById($authMgr->getMyOrganisationId())->getName() ?>
                </td>
                <?php } ?>
                <td>
                    <input type="submit" value="Voeg toe" class="button" />
                </td>
            </tr>
        </tfoot>
    <tbody>
        <?php foreach ($users as $user) { ?>
        <tr id="<?php echo $user->getId(); ?>">
            <td class="read_only"><?php echo $user->getId(); ?></td>
            <td class="read_only"><?php echo $user->getUsername(); ?></td>
            <td><?php echo $user->getDisplayName(); ?></td>
            <td><?php echo $user->getPwHash(); ?></td>
            <td>
            <?php
            $first = true;
            $sep = '';
            foreach ($driver->getGroupsOfUserViaUser($user) as $group) {
                echo $sep;
                if ($first) {
                    $sep = ', ';
                }
                echo $group->getName();
            }
            ?>
            </td>
            <td><?php
                $org = $driver->getOrganisationForUser($user);
                if ($org) {
                    echo $org->getName();
                } else {
                    echo 'No org!';
                }
                ?></td>
                <td class="read_only"></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
</form>
<p>Let op, als deze persoon ook een hunter is of de Android app gaat gebruiken, maak er ook een <a href="<?=WEBSITE_URL?>hunters">hunter</a> van!</p>
<?php
require_once BASE_DIR . 'footer.php';
?>