<style>
    .breadcrumb {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        font-size: 14px;
    }
    .breadcrumb a {
        color: #007bff;
        text-decoration: none;
    }
    .breadcrumb a:hover {
        text-decoration: underline;
    }
    .breadcrumb i {
        margin-right: 5px;
    }
    .breadcrumb span {
        margin-left: 5px;
        color: #6c757d;
    }
</style>

<nav class="breadcrumb">
    <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
    <span>></span>
    <span><?php echo isset($breadcrumbLabel) ? $breadcrumbLabel : 'Dashboard'; ?></span>
</nav>