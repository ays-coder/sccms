<?php
session_start();
require_once 'db_connect.php';

// Ensure only tutor can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    header("Location: ../login.php");
    exit();
}

$status = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emails = isset($_POST['selected_emails']) ? explode(',', $_POST['selected_emails']) : [];
    $subject = $_POST['subject'];
    $message = wordwrap($_POST['message'], 70);
    $headers = "From: tutor@smartcommercecore.com\r\n";

    $successCount = 0;
    $failCount = 0;

    foreach ($emails as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (mail($email, $subject, $message, $headers)) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
    }

    $status = "$successCount message(s) sent successfully.";
    if ($failCount > 0) {
        $status .= " $failCount message(s) failed to send.";
    }
}

// Fetch students
$students_query = "SELECT username, email FROM users WHERE role = 'student'";
$students_result = mysqli_query($conn, $students_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send Group Message | Tutor Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto mt-10 p-6 bg-white shadow-md rounded">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Send Group Message to Students</h2>
      <a href="tutor_dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded text-sm">
        ← Back to Dashboard
      </a>
    </div>

    <?php if (!empty($status)): ?>
      <div class="mb-4 p-3 rounded <?= strpos($status, 'failed') === false ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800' ?>">
        <?= htmlspecialchars($status) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <div>
        <label class="block mb-1 font-semibold">Available Students:</label>
        <select id="emailDropdown" class="w-full p-2 border rounded" multiple size="6">
          <?php mysqli_data_seek($students_result, 0); ?>
          <?php while ($student = mysqli_fetch_assoc($students_result)): ?>
            <option value="<?= htmlspecialchars($student['email']) ?>">
              <?= htmlspecialchars($student['username']) ?> (<?= htmlspecialchars($student['email']) ?>)
            </option>
          <?php endwhile; ?>
        </select>

        <div class="mt-2 flex gap-2">
          <button type="button" onclick="addSelectedEmails()" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 text-sm">
            Select
          </button>
          <button type="button" onclick="selectAllEmails()" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 text-sm">
            Select All
          </button>
          <button type="button" onclick="deselectAll()" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">
            Deselect
          </button>
        </div>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Selected Emails:</label>
        <textarea name="selected_emails" id="selectedEmails" readonly required
          class="w-full p-2 border rounded bg-gray-100 h-24 text-sm text-gray-700"
        ></textarea>
      </div>

      <div>
        <label class="block mb-1 font-semibold">Subject:</label>
        <input type="text" name="subject" required class="w-full p-2 border rounded">
      </div>

      <div>
        <label class="block mb-1 font-semibold">Message:</label>
        <textarea name="message" required class="w-full p-2 border rounded h-32"></textarea>
      </div>

      <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
        Send Message
      </button>
    </form>
  </div>

  <script>
    function addSelectedEmails() {
      const dropdown = document.getElementById('emailDropdown');
      const emailBox = document.getElementById('selectedEmails');
      const selectedOptions = Array.from(dropdown.selectedOptions).map(opt => opt.value);

      let currentEmails = emailBox.value ? emailBox.value.split(',').map(e => e.trim()) : [];

      selectedOptions.forEach(email => {
        if (!currentEmails.includes(email)) {
          currentEmails.push(email);
        }
      });

      emailBox.value = currentEmails.join(', ');
    }

    function selectAllEmails() {
      const dropdown = document.getElementById('emailDropdown');
      const emailBox = document.getElementById('selectedEmails');

      let allEmails = Array.from(dropdown.options).map(opt => opt.value);
      for (let i = 0; i < dropdown.options.length; i++) {
        dropdown.options[i].selected = true;
      }

      emailBox.value = allEmails.join(', ');
    }

    function deselectAll() {
      const dropdown = document.getElementById('emailDropdown');
      const emailBox = document.getElementById('selectedEmails');

      for (let i = 0; i < dropdown.options.length; i++) {
        dropdown.options[i].selected = false;
      }

      emailBox.value = "";
    }
  </script>
</body>
</html>
