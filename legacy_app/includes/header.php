<?php 
require_once($_SERVER['DOCUMENT_ROOT'] . '/session_handler.php');

// Start output buffering if not already started
if (ob_get_level() == 0) {
    ob_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Onyx Hub'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root { 
            --accent-gold: #f1c40f; 
            --gray-800: #1e293b; 
            --gray-900: #0f172a; 
            --text-muted: #64748b;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--gray-900);
            color: #fff;
            display: flex;
            flex-direction: column;
        }

        #main {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
        }

        /* Navigation Styling */
        .hotspot-nav {
            display: flex;
            gap: 10px;
            background: rgba(30, 41, 59, 0.5);
            padding: 8px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.05);
            flex-wrap: wrap;
        }

        .nav-link {
            padding: 10px 18px;
            border-radius: 12px;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .nav-link:hover { 
            color: #fff; 
            background: rgba(255,255,255,0.05); 
        }

        .nav-link.active { 
            background: var(--accent-gold); 
            color: #000; 
        }

        /* Stats Grid */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--gray-800);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: rgba(241, 196, 15, 0.3);
            transform: translateY(-4px);
        }

        .stat-label {
            font-size: 10px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 900;
            color: #fff;
            margin: 10px 0 0 0;
        }

        .stat-value span {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-left: 5px;
        }

        /* Card Styling */
        .card {
            background: var(--gray-800);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 12px;
            font-weight: 800;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            display: block;
        }

        .status-indicator {
            display: flex;
            gap: 12px;
            align-items: center;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            border-radius: 50%;
            background: var(--success);
            box-shadow: 0 0 12px var(--success);
            display: inline-block;
            flex-shrink: 0;
        }

        /* Insight Grid */
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .insight-item {
            text-align: center;
        }

        .insight-label {
            font-size: 9px;
            color: var(--text-muted);
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .insight-value {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
        }

        /* Table Styling */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 15px;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(0,0,0,0.3);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
        }

        tr:hover {
            background: rgba(255,255,255,0.02);
        }

        /* Page Title */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            gap: 20px;
        }

        .page-title {
            flex: 1;
        }

        .page-title h1 {
            margin: 0;
            font-weight: 900;
            font-size: 28px;
            text-transform: uppercase;
            font-style: italic;
            letter-spacing: 0.5px;
        }

        .page-title h1 .gold {
            color: var(--accent-gold);
        }

        .page-subtitle {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 8px;
            letter-spacing: 1px;
        }

        .page-nav {
            flex: 1;
            display: flex;
            justify-content: flex-end;
        }

        /* Two Column Layout */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            #main {
                padding: 25px;
            }

            .page-header {
                flex-direction: column;
            }

            .page-nav {
                justify-content: flex-start;
            }

            .two-column {
                grid-template-columns: 1fr;
            }

            .insight-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            #main {
                padding: 15px;
            }

            .page-title h1 {
                font-size: 20px;
            }

            .hotspot-nav {
                width: 100%;
            }

            .insight-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 24px;
            }

            th, td {
                padding: 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/includes/sidebar.php'); ?>

<div id="main">