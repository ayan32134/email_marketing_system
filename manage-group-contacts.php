<?php
session_start();
require_once 'classes/Member.php';
require_once 'classes/BaseModelHelper.php';
require_once 'config/Database.php';

// Ensure member is logged in
if (!isset($_SESSION['member_id'])) {
    die("Access denied.");
}

$member_id = $_SESSION['member_id'];
$db = new dataBase(['host' => 'localhost', 'user' => 'root', 'password' => 'root', 'database' => 'email_marketing_system']);

$group_id = (int)($_GET['group_id'] ?? 0);
if (!$group_id) {
    die("Group ID is required.");
}

// Verify group belongs to member
$group = BaseModelHelper::mysqliFind($db->db, 'ContactGroups', 'group_id', $group_id);
if (!$group || $group['member_id'] != $member_id) {
    die("Group not found or access denied.");
}

// Get contacts in this group
$groupMembers = BaseModelHelper::mysqliGetAll($db->db, 'Group_Members', ['group_id' => $group_id]);
$contactsInGroup = [];
foreach ($groupMembers as $gm) {
    $contact = BaseModelHelper::mysqliFind($db->db, 'Contacts', 'contact_id', $gm['contact_id']);
    if ($contact && $contact['member_id'] == $member_id) {
        $contactsInGroup[] = $contact;
    }
}

// Get all contacts for this member (for adding to group)
$allContacts = BaseModelHelper::mysqliGetAll($db->db, 'Contacts', ['member_id' => $member_id]);
$contactsInGroupIds = array_column($contactsInGroup, 'contact_id');
$availableContacts = array_filter($allContacts, function($c) use ($contactsInGroupIds) {
    return !in_array($c['contact_id'], $contactsInGroupIds);
});

$currentCount = count($contactsInGroup);
$maxContacts = $group['max_contacts'] ?? null;
$canAddMore = $maxContacts === null || $maxContacts == 0 || $currentCount < $maxContacts;
?>
<style>
    :root {
        --bg-primary: #0f172a;
        --bg-secondary: #1e293b;
        --bg-tertiary: #334155;
        --bg-hover: #475569;
        --text-primary: #f1f5f9;
        --text-secondary: #cbd5e1;
        --text-muted: #94a3b8;
        --accent: #3b82f6;
        --accent-hover: #2563eb;
        --success: #10b981;
        --danger: #ef4444;
        --border: #334155;
    }
    .group-info {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid var(--accent);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        color: var(--text-primary);
    }
    .group-info strong {
        color: var(--accent);
    }
    .contacts-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .contact-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border);
        background: var(--bg-secondary);
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
    .contact-item:last-child {
        border-bottom: none;
    }
    .contact-info {
        flex: 1;
    }
    .contact-name {
        font-weight: 500;
        color: var(--text-primary);
    }
    .contact-email {
        font-size: 0.875rem;
        color: var(--text-muted);
    }
    .btn-remove {
        padding: 0.375rem 0.75rem;
        background: var(--danger);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.8125rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
    }
    .btn-remove:hover {
        background: #dc2626;
    }
    .add-contacts-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border);
    }
    .add-contacts-section h4 {
        margin-bottom: 1rem;
        color: var(--text-primary);
    }
    .available-contacts {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0.5rem;
        background: var(--bg-secondary);
    }
    .available-contact-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid var(--border);
        background: var(--bg-primary);
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
    .available-contact-item:last-child {
        border-bottom: none;
    }
    .btn-add {
        padding: 0.375rem 0.75rem;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 0.8125rem;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
    }
    .btn-add:hover {
        background: var(--accent-hover);
    }
    .btn-add:disabled {
        background: var(--bg-tertiary);
        cursor: not-allowed;
        opacity: 0.5;
    }
    .limit-warning {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid var(--danger);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        color: var(--danger);
        font-size: 0.875rem;
    }
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: var(--bg-primary);
    }
    ::-webkit-scrollbar-thumb {
        background: var(--bg-tertiary);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--bg-hover);
    }
</style>
<script>
    function removeContactFromGroup(contactId, groupId) {
        fetch('process/remove-contact-from-group.php?contact_id=' + contactId + '&group_id=' + groupId, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the group contacts
                    if (window.parent && window.parent.loadGroupContacts) {
                        window.parent.loadGroupContacts(groupId);
                    } else if (window.loadGroupContacts) {
                        window.loadGroupContacts(groupId);
                    } else {
                        location.reload();
                    }
                } else {
                    alert(data.message || 'Error removing contact from group.');
                }
            })
            .catch(error => {
                alert('Error removing contact from group.');
            });
    }
</script>

<div class="group-info">
    <strong>Group:</strong> <?= htmlspecialchars($group['group_name']) ?><br>
    <strong>Current Contacts:</strong> <?= $currentCount ?>
    <?php if ($maxContacts !== null && $maxContacts > 0): ?>
        / <?= $maxContacts ?> (Limit)
    <?php else: ?>
        (Unlimited)
    <?php endif; ?>
</div>

<?php if (!$canAddMore): ?>
    <div class="limit-warning">
        ⚠️ Group limit reached! Remove some contacts before adding new ones.
    </div>
<?php endif; ?>

<h4 style="margin-bottom: 1rem; color: var(--text-primary);">Contacts in Group</h4>
<div class="contacts-list">
    <?php if (empty($contactsInGroup)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">No contacts in this group yet.</p>
    <?php else: ?>
        <?php foreach ($contactsInGroup as $contact): ?>
            <div class="contact-item">
                <div class="contact-info">
                    <div class="contact-name">
                        <?= htmlspecialchars(trim($contact['honorifics'] . ' ' . $contact['first_name'] . ' ' . $contact['middle_name'] . ' ' . $contact['last_name'])) ?>
                    </div>
                    <div class="contact-email"><?= htmlspecialchars($contact['email']) ?></div>
                </div>
                <a href="#" 
                   onclick="if(confirm('Remove this contact from group?')) { removeContactFromGroup(<?= $contact['contact_id'] ?>, <?= $group_id ?>); } return false;" 
                   class="btn-remove">Remove</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (!empty($availableContacts)): ?>
    <div class="add-contacts-section">
        <h4>Add Contacts to Group</h4>
        <div class="available-contacts">
            <?php foreach ($availableContacts as $contact): ?>
                <div class="available-contact-item">
                    <div class="contact-info">
                        <div class="contact-name">
                            <?= htmlspecialchars(trim($contact['honorifics'] . ' ' . $contact['first_name'] . ' ' . $contact['middle_name'] . ' ' . $contact['last_name'])) ?>
                        </div>
                        <div class="contact-email"><?= htmlspecialchars($contact['email']) ?></div>
                    </div>
                    <form method="POST" action="process/assign-contact-to-group.php" style="display: inline;" onsubmit="return handleAddContact(event, this);">
                        <input type="hidden" name="contact_id" value="<?= $contact['contact_id'] ?>">
                        <input type="hidden" name="group_id" value="<?= $group_id ?>">
                        <button type="submit" class="btn-add" <?= !$canAddMore ? 'disabled' : '' ?>>
                            Add
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="add-contacts-section">
        <p style="text-align: center; color: var(--text-muted); padding: 1rem;">
            All contacts are already in this group.
        </p>
    </div>
<?php endif; ?>

