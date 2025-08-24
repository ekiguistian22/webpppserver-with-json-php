<?php 
session_start();
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: index.php?error=timeout");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar PPPoE Secret - By Eki Guistian</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
    body { background-color: #1e1e1e; color: #f8f9fa; }
    h3 { margin: 0; }
    .status-online { color: #00ff9d; font-weight: bold; }
    .status-offline { color: #ff4c4c; font-weight: bold; }
    .pagination li { cursor:pointer; }
    .table thead th { background:#2c2c2c; color:#fff; }
    .table tbody tr:hover { background:#2a2a2a; }
    .page-link { background:#2c2c2c; border:1px solid #444; color:#fff; }
    .page-item.active .page-link { background:#0d6efd; border-color:#0d6efd; }
    .form-select, .form-control { background:#2c2c2c; color: #f8f9fa; border:1px solid #444; }
    .form-select:focus, .form-control:focus { background:#2c2c2c; color:#fff; border-color:#0d6efd; box-shadow:none; }
    .form-control::placeholder {
        color: #ffffff !important;
        opacity: 0.7;
    }
</style>
</head>
<body>
<div class="container py-4">

    <!-- Header + tombol -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Daftar PPPoE Pelanggan</h3>
        <div>
            <a href="tambah_secret" class="btn btn-primary btn-sm">Tambah Pelanggan</a>
            <a href="logout" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>

    <!-- Info jumlah PPP aktif -->
    <div class="alert alert-info">
        Jumlah Pelanggan Aktif: <strong><? require_once 'ppp_active_count.php'; ?></strong>
    </div>

    <!-- Filter + search -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-6 d-flex gap-2">
            <label class="d-flex align-items-center">Tampilkan 
                <select id="rowsPerPage" class="form-select form-select-sm mx-2 w-auto">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                </select> Status
            </label>
            <select id="filter" class="form-select form-select-sm w-auto">
                <option value="all">--Pilih Status--</option>
                <option value="all">Semua</option>
                <option value="online">Pelanggan Online</option>
                <option value="offline">Pelanggan Offline</option>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" id="search" class="form-control form-control-sm" autocomplete="off" placeholder="Cari Secret, IP Address atau Nama Pelanggan..">
        </div>
    </div>

    <!-- Tabel data -->
    <div class="table-responsive">
        <table class="table table-dark table-striped table-hover align-middle text-nowrap">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Paket</th>
                    <th>Status</th>
                    <th>IP</th>
                    <th>Nama Pelanggan</th>
                    <th>Terakhir Login/Logout</th>
                    <th style="width:150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="dataTable">
                <tr><td colspan="7" class="text-center">Loading data...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div id="infoTable" class="small"></div>
        <nav>
            <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
        </nav>
    </div>
</div>

<script>
let allData = [];
let rowsPerPage = 10;
let currentPage = 1;

function renderTable(){
    const filter = $("#filter").val();
    const search = $("#search").val().toLowerCase().trim();

    let filtered = allData.filter(item => {
        let matchFilter = (filter === "all" || item.status === filter);
        let haystack = (item.username+" "+item.profile+" "+item.ip+" "+item.comment+" "+item.last_seen).toLowerCase();
        let matchSearch = !search || haystack.indexOf(search) > -1;
        return matchFilter && matchSearch;
    });

    let totalPages = Math.ceil(filtered.length / rowsPerPage);
    if(currentPage > totalPages) currentPage = totalPages || 1;

    let start = (currentPage-1) * rowsPerPage;
    let pageData = filtered.slice(start, start+rowsPerPage);

    let html = "";
    if(pageData.length === 0){
        html = '<tr><td colspan="7" class="text-center">Tidak ada data.</td></tr>';
    } else {
        pageData.forEach(function(item){
            const isOnline = item.status === "online";
            const ipCol = isOnline ? `<a href="http://${item.ip}" target="_blank">${item.ip}</a>` : "-";
            const statusCol = isOnline ? `<span class="status-online">ONLINE</span>` : `<span class="status-offline">OFFLINE</span>`;

            let actionBtn = "";
            if(item.isIsolir){
                actionBtn += `<button class="btn btn-success btn-sm btn-buka" data-user="${item.username}">Buka</button>`;
            } else {
                actionBtn += `<button class="btn btn-warning btn-sm btn-isolir" data-user="${item.username}">Isolir</button>`;
            }
            if(isOnline){
                actionBtn += ` <button class="btn btn-danger btn-sm btn-kick" data-user="${item.username}">Kick</button>`;
            }

            html += `
            <tr>
                <td>${item.username}</td>
                <td>${item.profile}</td>
                <td>${statusCol}</td>
                <td>${ipCol}</td>
                <td>${item.comment}</td>
                <td>${item.last_seen}</td>
                <td>${actionBtn}</td>
            </tr>`;
        });
    }

    $("#dataTable").html(html);

    // info table
    let end = start + pageData.length;
    $("#infoTable").text(`Menampilkan ${start+1} sampai ${end} dari ${filtered.length} data`);

    // pagination UI
    let pagHtml = "";
    if(totalPages > 1){
        pagHtml += `<li class="page-item ${currentPage===1?'disabled':''}"><a class="page-link">Prev</a></li>`;
        
        let maxShow = 10; // jumlah maksimal nomor halaman ditampilkan
        let startPage = Math.max(1, currentPage - Math.floor(maxShow/2));
        let endPage = startPage + maxShow - 1;
        if(endPage > totalPages){
            endPage = totalPages;
            startPage = Math.max(1, endPage - maxShow + 1);
        }

        if(startPage > 1){
            pagHtml += `<li class="page-item"><a class="page-link">1</a></li>`;
            if(startPage > 2) pagHtml += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
        }

        for(let i=startPage; i<=endPage; i++){
            pagHtml += `<li class="page-item ${i===currentPage?'active':''}"><a class="page-link">${i}</a></li>`;
        }

        if(endPage < totalPages){
            if(endPage < totalPages-1) pagHtml += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
            pagHtml += `<li class="page-item"><a class="page-link">${totalPages}</a></li>`;
        }

        pagHtml += `<li class="page-item ${currentPage===totalPages?'disabled':''}"><a class="page-link">Next</a></li>`;
    }
    $("#pagination").html(pagHtml);
}

function loadData(){
    $.getJSON("get_data.php", function(res){
        if(res.error){
            alert("Sesi habis, silakan login ulang.");
            window.location = "index.php?error=timeout";
            return;
        }
        $("#pppCount").text(res.totalActive);
        allData = res.data;
        currentPage = 1;
        renderTable();
    });
}

$(document).ready(function(){
    loadData();

    $("#filter, #search").on("input", function(){
        currentPage = 1;
        renderTable();
    });

    $("#rowsPerPage").on("change", function(){
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        renderTable();
    });

    $("#pagination").on("click", ".page-item:not(.disabled)", function(){
        const text = $(this).text();
        if(text === "Prev" && currentPage > 1) currentPage--;
        else if(text === "Next") currentPage++;
        else if(!isNaN(parseInt(text))) currentPage = parseInt(text);
        renderTable();
    });

    $(document).on("click", ".btn-isolir, .btn-buka, .btn-kick", function(){
        const u = $(this).data("user");
        const action = $(this).hasClass("btn-isolir") ? "isolir" : 
                       $(this).hasClass("btn-buka")   ? "buka"   : "kick";
        if(confirm("Yakin ingin " + action + " user " + u + " ?")){
            $.post("get_data.php", {action:action, user:u}, function(res){
                if(res.includes("OK")){
                    loadData();
                } else {
                    alert("Gagal aksi: "+res);
                }
            });
        }
    });
});
</script>
</body>
</html>
