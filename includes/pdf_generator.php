<?php
/**
 * Simple PDF Generator Helper
 * Creates basic PDF files using a simple approach
 */

function generateSimplePDF($html, $filename) {
    // This is a fallback function that outputs HTML that can be printed to PDF
    // For production, install DomPDF or mPDF via Composer
    
    // Try DomPDF first if available
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            echo $dompdf->output();
            return;
        } catch (Exception $e) {
            error_log("DomPDF Error: " . $e->getMessage());
        }
    }
    
    // Fallback: output as HTML with print styles
    // This allows users to print to PDF using browser's print function
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($filename) . '</title>
        <style>
            @media print {
                body { margin: 0; padding: 0; }
                .no-print { display: none; }
            }
            body { font-family: Arial, sans-serif; }
            .print-button {
                margin-bottom: 20px;
                padding: 10px;
                text-align: center;
                background-color: #f5f5f5;
                border-bottom: 1px solid #ddd;
            }
            .print-button button {
                padding: 8px 15px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
            }
            .print-button button:hover {
                background-color: #0056b3;
            }
        </style>
        <script>
            function printPDF() {
                window.print();
            }
        </script>
    </head>
    <body>
        <div class="print-button no-print">
            <button onclick="printPDF()">Print / Save as PDF</button>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">
                Click the button above and select "Save as PDF" in your browser\'s print dialog
            </p>
        </div>
        <div class="content">
            ' . $html . '
        </div>
    </body>
    </html>';
}
?>

Name:Aswin
EOD Report for Today- 04/02/26
Work Status - In Process
Project: Internal management system.

Work:
Verified database connectivity and query execution using the existing connection configuration.
Tested CRUD operations and edge cases to confirm stable data flow between frontend actions and backend responses.Notification modules notification list, count, and read handling
Database connection and validation utilities.

Name - Pradeebaa
EOD Report for Today- 04/02/26
Work Status - In Process
Project: Internal Management System Website

Work:
Designed and developed the Create User form and User List module within the internal management dashboard.
Implemented fields for username, email, password, department, and role with structured layout and validation-ready design.
Built a tabular view to display users along with action controls for efficient user management.

Name - Sweetline
EOD Report for Today- 04/02/26
Project: Internal management system

Work:
Created the Create Task page with clearly defined task details and respective deadlines.
Implemented a structured layout that enables admins to quickly monitor ongoing work and due dates.
Improved usability by organizing task information in a simple and easy-to-read format.
This enhancement strengthens overall task tracking and internal management efficiency.