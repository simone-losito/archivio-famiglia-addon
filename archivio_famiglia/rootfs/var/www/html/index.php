<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/functions.php';
$checkInstall = $conn->query("SHOW TABLES LIKE 'utenti'");
if (!$checkInstall || $checkInstall->num_rows === 0) {
    header("Location: install.php");
    exit;
}

requireLogin();

$msg = $_GET['msg'] ?? '';
$view = $_GET['view'] ?? 'card';
if (!in_array($view, ['card', 'list'], true)) $view = 'card';

$search = trim($_GET['search'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$anno = trim($_GET['anno'] ?? '');
$mese = trim($_GET['mese'] ?? '');
$dataPrecisa = trim($_GET['data_precisa'] ?? '');
$soloPreferiti = isset($_GET['preferiti']) && $_GET['preferiti'] === '1';

$categories = getCategories();

$categoryImages = [];
$imgRes = $conn->query("SELECT slug, immagine FROM categorie");
if ($imgRes) {
    while ($r = $imgRes->fetch_assoc()) {
        $categoryImages[$r['slug']] = $r['immagine'];
    }
}

$totalDocs = 0;
$totalResult = $conn->query("SELECT COUNT(*) AS totale FROM documenti");
if ($totalResult) $totalDocs = (int)$totalResult->fetch_assoc()['totale'];

$totalPreferiti = 0;
$prefResult = $conn->query("SELECT COUNT(*) AS totale FROM documenti WHERE preferito = 1");
if ($prefResult) $totalPreferiti = (int)$prefResult->fetch_assoc()['totale'];

$senzaData = 0;
$noDateResult = $conn->query("SELECT COUNT(*) AS totale FROM documenti WHERE data_documento IS NULL");
if ($noDateResult) $senzaData = (int)$noDateResult->fetch_assoc()['totale'];

$categoryCounts = [];
$countResult = $conn->query("SELECT categoria, COUNT(*) AS totale FROM documenti GROUP BY categoria");
if ($countResult) {
    while ($r = $countResult->fetch_assoc()) {
        $categoryCounts[$r['categoria']] = (int)$r['totale'];
    }
}

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(titolo LIKE ? OR nome_originale LIKE ? OR nome_archivio LIKE ? OR note LIKE ? OR tags LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= 'sssss';
}

if ($categoria !== '' && isset($categories[$categoria])) {
    $where[] = "categoria = ?";
    $params[] = $categoria;
    $types .= 's';
}

if ($anno !== '' && preg_match('/^\d{4}$/', $anno)) {
    $where[] = "YEAR(data_documento) = ?";
    $params[] = (int)$anno;
    $types .= 'i';
}

if ($mese !== '' && preg_match('/^\d{1,2}$/', $mese)) {
    $meseInt = (int)$mese;
    if ($meseInt >= 1 && $meseInt <= 12) {
        $where[] = "MONTH(data_documento) = ?";
        $params[] = $meseInt;
        $types .= 'i';
    }
}

if ($dataPrecisa !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPrecisa)) {
    $where[] = "data_documento = ?";
    $params[] = $dataPrecisa;
    $types .= 's';
}

if ($soloPreferiti) {
    $where[] = "preferito = 1";
}

$sql = "SELECT * FROM documenti";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY preferito DESC, CASE WHEN data_documento IS NULL THEN 1 ELSE 0 END, data_documento DESC, id DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$filteredCount = $result ? $result->num_rows : 0;

$recentDocs = $conn->query("SELECT * FROM documenti ORDER BY id DESC LIMIT 5");
$favDocs = $conn->query("SELECT * FROM documenti WHERE preferito = 1 ORDER BY id DESC LIMIT 6");

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function fmtDate(?string $date): string {
    if (!$date) return 'Senza data';
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : 'Senza data';
}

function extIcon(string $filename): string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf' => '📄',
        'jpg','jpeg','png','gif','webp' => '🖼️',
        'doc','docx' => '📝',
        'xls','xlsx','csv' => '📊',
        'zip','rar','7z','tar','gz' => '🗜️',
        default => '📎'
    };
}

function docTitle(array $row): string {
    $titolo = trim((string)($row['titolo'] ?? ''));
    if ($titolo !== '') return $titolo;
    return trim((string)($row['nome_originale'] ?? 'Documento'));
}

function buildBackUrl(): string {
    return $_SERVER['REQUEST_URI'] ?? 'index.php';
}

$queryBase = http_build_query([
    'search' => $search,
    'categoria' => $categoria,
    'anno' => $anno,
    'mese' => $mese,
    'data_precisa' => $dataPrecisa,
    'preferiti' => $soloPreferiti ? '1' : '',
]);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Archivio Famiglia</title>
<link rel="stylesheet" href="assets/css/archivio.css">
<style>
.dashboard-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:rgba(2,6,23,.45);border:1px solid var(--line);border-radius:22px;padding:18px;min-height:112px}
.stat-card strong{font-size:30px;display:block;margin-top:8px}
.upload-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}

.category-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px}
.category-tile{background:linear-gradient(145deg,rgba(2,6,23,.58),rgba(15,23,42,.88));border:1px solid var(--line);border-radius:30px;padding:18px;min-height:285px;text-decoration:none;color:var(--text);display:flex;flex-direction:column;gap:14px;box-shadow:0 14px 35px rgba(0,0,0,.16)}
.category-tile:hover{border-color:rgba(34,211,238,.6);text-decoration:none;transform:translateY(-1px)}
.category-tile img{width:100%;height:170px;object-fit:cover;border-radius:24px;border:1px solid var(--line)}
.category-tile .empty-img{width:100%;height:170px;border-radius:24px;border:1px dashed var(--line);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:42px}
.category-tile strong{font-size:24px;line-height:1.15;display:block}
.category-tile small{font-size:15px}

.mini-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px}
.mini-doc{background:rgba(2,6,23,.45);border:1px solid var(--line);border-radius:20px;padding:15px;display:flex;gap:12px;align-items:flex-start;text-decoration:none;color:var(--text)}
.mini-doc:hover{border-color:rgba(34,211,238,.5);text-decoration:none}
.mini-icon{width:44px;height:44px;border-radius:14px;background:rgba(34,211,238,.10);border:1px solid rgba(34,211,238,.25);display:flex;align-items:center;justify-content:center;font-size:22px;flex:0 0 auto}
.mini-title{font-weight:800;line-height:1.25}
.mini-sub{font-size:12px;color:var(--muted);margin-top:4px}

.doc-card{background:rgba(2,6,23,.45);border:1px solid var(--line);border-radius:24px;padding:18px;min-height:310px;display:flex;flex-direction:column;gap:12px;cursor:pointer;transition:.18s}
.doc-card:hover{border-color:rgba(34,211,238,.55);transform:translateY(-1px)}
.file.clickable-row{cursor:pointer;transition:.18s}
.file.clickable-row:hover{border-color:rgba(34,211,238,.55)}
.doc-head{display:flex;gap:14px;align-items:flex-start}
.doc-icon{width:56px;height:56px;border-radius:18px;background:rgba(34,211,238,.10);border:1px solid rgba(34,211,238,.25);display:flex;align-items:center;justify-content:center;font-size:28px;flex:0 0 auto}
.doc-title{font-size:19px;font-weight:900;line-height:1.25;word-break:break-word}
.doc-code{color:var(--muted);font-size:12px;margin-top:4px}
.doc-meta{display:flex;flex-wrap:wrap;gap:8px}
.doc-pill{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;background:rgba(148,163,184,.08);border:1px solid var(--line);color:var(--muted);font-size:12px}
.doc-note{color:var(--muted);font-size:13px;line-height:1.35;max-height:54px;overflow:hidden}
.doc-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:auto}
.doc-action{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 13px;border-radius:999px;background:rgba(34,211,238,.10);border:1px solid rgba(34,211,238,.35);color:var(--cyan);font-weight:bold;text-decoration:none}
.doc-action:hover{text-decoration:none;background:rgba(34,211,238,.18)}
.doc-action.danger{background:rgba(251,113,133,.12);border-color:rgba(251,113,133,.35);color:var(--red)}
.doc-action.star{background:rgba(250,204,21,.12);border-color:rgba(250,204,21,.45);color:var(--yellow)}
.doc-list-row{display:grid;grid-template-columns:56px 1fr 360px;gap:14px;align-items:center}
.original-name{font-size:12px;color:var(--muted);margin-top:4px;word-break:break-word}
.open-hint{font-size:12px;color:var(--cyan);margin-top:2px}
.quick-filter{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}

@media(max-width:1000px){
    .upload-grid{grid-template-columns:1fr}
    .doc-list-row{grid-template-columns:1fr}
    .category-grid{grid-template-columns:repeat(auto-fill,minmax(190px,1fr))}
    .category-tile{min-height:230px}
    .category-tile img,.category-tile .empty-img{height:125px}
}
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">📁 Archivio</div>
    <div class="menu">
        <a href="index.php" class="active">🏠 Home</a>
        <a href="categorie.php">⚙️ Categorie</a>
        <?php if(isAdmin()): ?>
            <a href="utenti.php">👥 Utenti</a>
            <a href="backup.php">💾 Backup</a>
        <?php endif; ?>
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<div class="main">

    <div class="card">
        <div class="topbar">
            <div>
                <span class="badge">Archivio attivo</span>
                <h1>Archivio Famiglia</h1>
                <p>Gestione documenti, pratiche, referti e file familiari.</p>
            </div>
            <div class="toolbar">
                <span class="badge">👤 <?= h($_SESSION['username'] ?? '') ?></span>
                <a class="btn btn-secondary" href="backup.php">💾 Backup</a>
            </div>
        </div>
        <?php if($msg): ?><p class="success"><?= h($msg) ?></p><?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <div class="stat-card"><small>Documenti totali</small><strong><?= (int)$totalDocs ?></strong></div>
        <div class="stat-card"><small>Preferiti</small><strong><?= (int)$totalPreferiti ?></strong></div>
        <div class="stat-card"><small>Risultati visualizzati</small><strong><?= (int)$filteredCount ?></strong></div>
        <div class="stat-card"><small>Senza data</small><strong><?= (int)$senzaData ?></strong></div>
    </div>

    <?php if($favDocs && $favDocs->num_rows > 0): ?>
    <div class="card">
        <div class="topbar">
            <div>
                <h2>⭐ Documenti importanti</h2>
                <p>Accesso rapido ai documenti segnati come preferiti.</p>
            </div>
            <a class="btn btn-secondary" href="index.php?preferiti=1">Vedi tutti i preferiti</a>
        </div>

        <div class="mini-grid">
            <?php while($f = $favDocs->fetch_assoc()): ?>
                <?php
                    $cat = $f['categoria'];
                    $url = "view.php?category=" . urlencode($cat) . "&file=" . urlencode($f['nome_archivio']);
                ?>
                <a class="mini-doc" href="<?= h($url) ?>" target="_blank">
                    <div class="mini-icon"><?= extIcon($f['nome_archivio']) ?></div>
                    <div>
                        <div class="mini-title"><?= h(docTitle($f)) ?></div>
                        <div class="mini-sub"><?= h($categories[$cat] ?? $cat) ?> · <?= h(fmtDate($f['data_documento'] ?? null)) ?></div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="topbar">
            <div>
                <h2>🕘 Documenti recenti</h2>
                <p>Ultimi documenti caricati nell’archivio.</p>
            </div>
        </div>

        <div class="mini-grid">
            <?php if($recentDocs && $recentDocs->num_rows > 0): ?>
                <?php while($r = $recentDocs->fetch_assoc()): ?>
                    <?php
                        $cat = $r['categoria'];
                        $url = "view.php?category=" . urlencode($cat) . "&file=" . urlencode($r['nome_archivio']);
                    ?>
                    <a class="mini-doc" href="<?= h($url) ?>" target="_blank">
                        <div class="mini-icon"><?= extIcon($r['nome_archivio']) ?></div>
                        <div>
                            <div class="mini-title"><?= h(docTitle($r)) ?></div>
                            <div class="mini-sub"><?= h($categories[$cat] ?? $cat) ?> · <?= h(fmtDate($r['data_documento'] ?? null)) ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Nessun documento recente.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2>Categorie rapide</h2>
                <p>Card grandi con immagine categoria ben visibile.</p>
            </div>
            <a class="btn btn-secondary" href="categorie.php">⚙️ Gestisci categorie</a>
        </div>

        <div class="category-grid">
            <?php foreach($categories as $key => $label): ?>
                <a class="category-tile" href="index.php?categoria=<?= urlencode($key) ?>">
                    <?php if(!empty($categoryImages[$key])): ?>
                        <img src="<?= h($categoryImages[$key]) ?>">
                    <?php else: ?>
                        <div class="empty-img">📂</div>
                    <?php endif; ?>
                    <div>
                        <strong><?= h($label) ?></strong>
                        <small><?= $categoryCounts[$key] ?? 0 ?> documenti</small>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Ricerca documenti</h2>

        <div class="quick-filter">
            <a class="badge" href="index.php">Tutti</a>
            <a class="badge" href="index.php?preferiti=1">⭐ Solo preferiti</a>
            <a class="badge" href="index.php?anno=<?= date('Y') ?>">Anno <?= date('Y') ?></a>
        </div>

        <form method="GET">
            <div class="upload-grid">
                <div>
                    <label>Cerca per nome documento</label>
                    <input type="text" name="search" placeholder="Nome documento, file, codice DOC, tag o note..." value="<?= h($search) ?>">
                </div>

                <div>
                    <label>Categoria</label>
                    <select name="categoria">
                        <option value="">Tutte le categorie</option>
                        <?php foreach($categories as $key => $label): ?>
                            <option value="<?= h($key) ?>" <?= $categoria === $key ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Anno pratica</label>
                    <input type="number" name="anno" placeholder="Es. 2026" value="<?= h($anno) ?>">
                </div>

                <div>
                    <label>Mese pratica</label>
                    <select name="mese">
                        <option value="">Tutti i mesi</option>
                        <?php
                        $mesi = [1=>'Gennaio',2=>'Febbraio',3=>'Marzo',4=>'Aprile',5=>'Maggio',6=>'Giugno',7=>'Luglio',8=>'Agosto',9=>'Settembre',10=>'Ottobre',11=>'Novembre',12=>'Dicembre'];
                        foreach($mesi as $num => $nome):
                        ?>
                            <option value="<?= $num ?>" <?= (string)$mese === (string)$num ? 'selected' : '' ?>><?= h($nome) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Giorno preciso</label>
                    <input type="date" name="data_precisa" value="<?= h($dataPrecisa) ?>">
                </div>

                <div>
                    <label>Preferiti</label>
                    <select name="preferiti">
                        <option value="">Tutti</option>
                        <option value="1" <?= $soloPreferiti ? 'selected' : '' ?>>Solo preferiti</option>
                    </select>
                </div>
            </div>

            <div class="toolbar">
                <button>Cerca</button>
                <a class="btn btn-secondary" href="index.php">Reset</a>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Carica nuovo documento</h2>
        <form method="POST" action="upload.php" enctype="multipart/form-data">
            <div class="upload-grid">
                <div>
                    <label>Nome documento</label>
                    <input type="text" name="titolo" placeholder="Esempio: Visita cardiologica papà 2026" required>
                </div>

                <div>
                    <label>Categoria</label>
                    <select name="category" required>
                        <?php foreach($categories as $key => $label): ?>
                            <option value="<?= h($key) ?>"><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Data pratica</label>
                    <input type="date" name="data_documento">
                </div>

                <div>
                    <label>Tag</label>
                    <input type="text" name="tags" placeholder="Es. cardiologia, bolletta, banca...">
                </div>

                <div>
                    <label>File documento</label>
                    <input type="file" name="file" required>
                </div>
            </div>

            <label>Note</label>
            <textarea name="note" placeholder="Note pratica..." style="min-height:90px;"></textarea>

            <button>⬆️ Carica documento</button>
        </form>
    </div>

    <div class="card">
        <div class="topbar">
            <div>
                <h2>Documenti</h2>
                <p><?= $filteredCount === $totalDocs ? 'Tutti i documenti presenti in archivio.' : ((int)$filteredCount . ' risultati filtrati su ' . (int)$totalDocs . ' documenti.') ?></p>
            </div>
            <div class="toolbar">
                <a class="badge" href="index.php?<?= h($queryBase ? $queryBase . '&' : '') ?>view=card">▦ Card</a>
                <a class="badge" href="index.php?<?= h($queryBase ? $queryBase . '&' : '') ?>view=list">☰ Elenco</a>
            </div>
        </div>

        <?php if(!$result || $result->num_rows === 0): ?>

            <p>Nessun documento trovato.</p>

        <?php elseif($view === 'list'): ?>

            <?php while($row = $result->fetch_assoc()): ?>
                <?php
                    $cat = $row['categoria'];
                    $catName = $categories[$cat] ?? $cat;
                    $icon = extIcon($row['nome_archivio']);
                    $title = docTitle($row);
                    $previewUrl = "view.php?category=" . urlencode($cat) . "&file=" . urlencode($row['nome_archivio']);
                    $starUrl = "toggle_preferito.php?id=" . (int)$row['id'] . "&back=" . urlencode(buildBackUrl());
                ?>
                <div class="file clickable-row" data-open="<?= h($previewUrl) ?>">
                    <div class="doc-list-row">
                        <div class="doc-icon"><?= $icon ?></div>
                        <div>
                            <div class="doc-title"><?= ((int)($row['preferito'] ?? 0) === 1 ? '⭐ ' : '') ?><?= h($title) ?></div>
                            <div class="open-hint">Clicca sulla riga per aprire l’anteprima</div>
                            <div class="original-name">File originale: <?= h($row['nome_originale']) ?></div>
                            <div class="doc-code">Archivio: <?= h($row['nome_archivio']) ?></div>
                            <div class="doc-meta">
                                <span class="doc-pill">📂 <?= h($catName) ?></span>
                                <span class="doc-pill">📅 <?= h(fmtDate($row['data_documento'] ?? null)) ?></span>
                                <?php if(!empty($row['tags'])): ?><span class="doc-pill">🏷️ <?= h($row['tags']) ?></span><?php endif; ?>
                            </div>
                            <?php if(!empty($row['note'])): ?><div class="doc-note"><?= nl2br(h($row['note'])) ?></div><?php endif; ?>
                        </div>
                        <div class="doc-actions">
                            <a class="doc-action star" href="<?= h($starUrl) ?>"><?= ((int)($row['preferito'] ?? 0) === 1 ? '★ Preferito' : '☆ Preferito') ?></a>
                            <a class="doc-action" href="<?= h($previewUrl) ?>" target="_blank">👁️ Visualizza</a>
                            <a class="doc-action" href="download.php?category=<?= urlencode($cat) ?>&file=<?= urlencode($row['nome_archivio']) ?>">⬇️ Scarica</a>
                            <a class="doc-action" href="edit_documento.php?id=<?= (int)$row['id'] ?>">✏️ Modifica</a>
                            <a class="doc-action danger" href="delete.php?category=<?= urlencode($cat) ?>&file=<?= urlencode($row['nome_archivio']) ?>" onclick="return confirm('Eliminare questo documento?')">🗑️ Elimina</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php else: ?>

            <div class="grid-cards">
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php
                        $cat = $row['categoria'];
                        $catName = $categories[$cat] ?? $cat;
                        $icon = extIcon($row['nome_archivio']);
                        $title = docTitle($row);
                        $previewUrl = "view.php?category=" . urlencode($cat) . "&file=" . urlencode($row['nome_archivio']);
                        $starUrl = "toggle_preferito.php?id=" . (int)$row['id'] . "&back=" . urlencode(buildBackUrl());
                    ?>
                    <div class="doc-card" data-open="<?= h($previewUrl) ?>">
                        <div class="doc-head">
                            <div class="doc-icon"><?= $icon ?></div>
                            <div>
                                <div class="doc-title"><?= ((int)($row['preferito'] ?? 0) === 1 ? '⭐ ' : '') ?><?= h($title) ?></div>
                                <div class="open-hint">Clicca sulla card per aprire l’anteprima</div>
                                <div class="original-name">File: <?= h($row['nome_originale']) ?></div>
                                <div class="doc-code">Archivio: <?= h($row['nome_archivio']) ?></div>
                            </div>
                        </div>

                        <div class="doc-meta">
                            <span class="doc-pill">📂 <?= h($catName) ?></span>
                            <span class="doc-pill">📅 <?= h(fmtDate($row['data_documento'] ?? null)) ?></span>
                        </div>

                        <?php if(!empty($row['tags'])): ?>
                            <div class="doc-pill">🏷️ <?= h($row['tags']) ?></div>
                        <?php endif; ?>

                        <div class="doc-note">
                            <?= !empty($row['note']) ? nl2br(h($row['note'])) : 'Nessuna nota inserita.' ?>
                        </div>

                        <div class="doc-actions">
                            <a class="doc-action star" href="<?= h($starUrl) ?>"><?= ((int)($row['preferito'] ?? 0) === 1 ? '★ Preferito' : '☆ Preferito') ?></a>
                            <a class="doc-action" href="<?= h($previewUrl) ?>" target="_blank">👁️ Visualizza</a>
                            <a class="doc-action" href="download.php?category=<?= urlencode($cat) ?>&file=<?= urlencode($row['nome_archivio']) ?>">⬇️ Scarica</a>
                            <a class="doc-action" href="edit_documento.php?id=<?= (int)$row['id'] ?>">✏️ Modifica</a>
                            <a class="doc-action danger" href="delete.php?category=<?= urlencode($cat) ?>&file=<?= urlencode($row['nome_archivio']) ?>" onclick="return confirm('Eliminare questo documento?')">🗑️ Elimina</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php endif; ?>
    </div>

</div>

<script>
document.querySelectorAll('[data-open]').forEach(function(card){
    card.addEventListener('click', function(e){
        if (e.target.closest('a, button, input, select, textarea, form')) {
            return;
        }
        window.open(card.dataset.open, '_blank');
    });
});
</script>
<script src="assets/js/theme.js"></script>
</body>
</html>
