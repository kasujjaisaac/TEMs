<?php
require_once __DIR__ . '/includes/erp_layout.php';

$context = onyx_page_start('Products', 'Product catalog for pricing, stock levels, suppliers, images, variants, and units.');
$currency = $context['currency'];
$tenant_id = onyx_tenant_id();
$products = array_map(
    static fn (array $row): array => [
        $row['sku'],
        $row['name'],
        $row['category_name'] ?: '-',
        onyx_money((float) $row['buying_price'], $currency),
        onyx_money((float) $row['selling_price'], $currency),
        $row['current_stock'],
        $row['min_stock'],
        '-',
    ],
    onyx_rows(
        'SELECT p.sku, p.name, p.buying_price, p.selling_price, p.current_stock, p.min_stock,
                pc.name AS category_name
         FROM products p
         LEFT JOIN product_categories pc ON pc.id = p.product_category_id AND pc.tenant_id = p.tenant_id
         WHERE p.tenant_id = :tenant_id
         ORDER BY p.name ASC',
        ['tenant_id' => $tenant_id]
    )
);
    $product_options = onyx_rows(
        'SELECT id, name FROM products WHERE tenant_id = :tenant_id ORDER BY name ASC',
        ['tenant_id' => $tenant_id]
    );

?>

<div class="module-grid">
    <?php onyx_panel_start('Product Actions', 'fa-box-open', 'span-12'); ?>
        <?php onyx_action_grid([
            ['label' => 'Add Product', 'icon' => 'fa-plus', 'href' => 'products_action.php?action=add'],
            ['label' => 'Add Product Category', 'icon' => 'fa-tags', 'href' => 'products_action.php?action=add_category&type=product'],
            ['label' => 'Add Income Category', 'icon' => 'fa-coins', 'href' => 'products_action.php?action=add_category&type=income'],
            ['label' => 'Add Expense Category', 'icon' => 'fa-receipt', 'href' => 'products_action.php?action=add_category&type=expense'],
            ['label' => 'Update Prices', 'icon' => 'fa-tags', 'href' => 'products_action.php?action=bulk_update'],
            ['label' => 'Export Products', 'icon' => 'fa-file-export', 'href' => 'products_action.php?action=export'],
        ]); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Product Catalog', 'fa-boxes-stacked', 'span-12'); ?>
        <div style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
            <label class="muted" for="selected_product">Select Product:</label>
            <select id="selected_product" style="padding:8px; min-width:220px;">
                <option value="">-- choose product --</option>
                <?php foreach ($product_options as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['id']) ?>"><?= htmlspecialchars($opt['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <a id="prodEdit" class="action-btn" href="#">Edit</a>
            <a id="prodDelete" class="action-btn" href="#">Delete</a>
            <a id="prodView" class="action-btn" href="#">View</a>
        </div>

        <?php onyx_table(
            ['SKU', 'Product Name', 'Category', 'Cost Price', 'Selling Price', 'Current Stock', 'Reorder Level', 'Supplier'],
            $products
        ); ?>
    <?php onyx_panel_end(); ?>

    <?php onyx_panel_start('Features', 'fa-layer-group', 'span-12'); ?>
        <div class="profile-grid">
            <div class="profile-card"><strong>Product Images</strong><span>Attach catalog images for sales, POS, and product profiles.</span></div>
            <div class="profile-card"><strong>Product Variants</strong><span>Manage sizes, colors, packs, models, and branch-specific versions.</span></div>
            <div class="profile-card"><strong>Product Categories</strong><span>Group products by department, revenue line, or inventory class.</span></div>
            <div class="profile-card"><strong>Units of Measure</strong><span>Support pieces, cartons, kilograms, liters, and custom units.</span></div>
        </div>
    <?php onyx_panel_end(); ?>
</div>

<?php onyx_page_end(); ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('selected_product');
    const btnEdit = document.getElementById('prodEdit');
    const btnDelete = document.getElementById('prodDelete');
    const btnView = document.getElementById('prodView');

    function updateButtons(){
        const id = sel.value;
        const base = 'products_action.php';
        const disabledStyle = 'opacity:0.6; pointer-events:none;';
        if (!id) {
            btnEdit.setAttribute('style', disabledStyle);
            btnDelete.setAttribute('style', disabledStyle);
            btnView.setAttribute('style', disabledStyle);
            btnEdit.href = '#'; btnDelete.href = '#'; btnView.href = '#';
            return;
        }
        btnEdit.style = '';
        btnDelete.style = '';
        btnView.style = '';
        btnEdit.href = base + '?action=edit&id=' + encodeURIComponent(id);
        btnDelete.href = base + '?action=delete&id=' + encodeURIComponent(id);
        btnView.href = base + '?action=view&id=' + encodeURIComponent(id);
    }

    sel.addEventListener('change', updateButtons);
    updateButtons();
});
// modal and AJAX handlers
<style>
/* Simple modal */
.onyx-modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;z-index:9999}
.onyx-modal{background:#fff;border-radius:6px;max-width:900px;width:96%;max-height:90vh;overflow:auto;padding:16px;box-shadow:0 6px 24px rgba(0,0,0,0.2)}
.onyx-modal .modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:12px}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const sel = document.getElementById('selected_product');
    const btnEdit = document.getElementById('prodEdit');
    const btnDelete = document.getElementById('prodDelete');
    const btnView = document.getElementById('prodView');

    function updateButtons(){
        const id = sel.value;
        const base = 'products_action.php';
        const disabledStyle = 'opacity:0.6; pointer-events:none;';
        if (!id) {
            btnEdit.setAttribute('style', disabledStyle);
            btnDelete.setAttribute('style', disabledStyle);
            btnView.setAttribute('style', disabledStyle);
            btnEdit.dataset.href = '#'; btnDelete.dataset.href = '#'; btnView.dataset.href = '#';
            return;
        }
        btnEdit.style = '';
        btnDelete.style = '';
        btnView.style = '';
        btnEdit.dataset.href = base + '?action=edit&id=' + encodeURIComponent(id);
        btnDelete.dataset.href = base + '?action=delete&id=' + encodeURIComponent(id);
        btnView.dataset.href = base + '?action=view&id=' + encodeURIComponent(id);
    }

    function openModal(html){
        // create backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'onyx-modal-backdrop';
        const modal = document.createElement('div');
        modal.className = 'onyx-modal';
        modal.innerHTML = html;
        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);

        backdrop.addEventListener('click', function(e){
            if (e.target === backdrop) backdrop.remove();
        });

        // attach close links inside modal
        const closeBtns = modal.querySelectorAll('.modal-close');
        closeBtns.forEach(b=>b.addEventListener('click', ()=>backdrop.remove()));

        return { backdrop, modal };
    }

    async function fetchAndShow(url){
        const res = await fetch(url, { credentials: 'same-origin' });
        const text = await res.text();
        // try to extract the first form or content area
        let parser = new DOMParser();
        let doc = parser.parseFromString(text, 'text/html');
        let content = '';
        const form = doc.querySelector('form');
        if (form) content = form.outerHTML;
        else {
            const panel = doc.querySelector('.panel') || doc.body;
            content = panel.innerHTML;
        }
        const { backdrop, modal } = openModal(content + '<div class="modal-actions"><button class="modal-close action-btn">Close</button></div>');

        // attach form submit handler if present
        const insertedForm = modal.querySelector('form');
        if (insertedForm) {
            insertedForm.addEventListener('submit', async function(e){
                e.preventDefault();
                const fd = new FormData(insertedForm);
                // ensure action field exists
                const action = fd.get('action') || insertedForm.querySelector('input[name="action"]')?.value;
                const resp = await fetch('products_action.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                if (resp.redirected) {
                    window.location.href = resp.url;
                    return;
                }
                const txt = await resp.text();
                // close modal and reload to reflect changes
                backdrop.remove();
                window.location.reload();
            });
        }
    }

    sel.addEventListener('change', updateButtons);
    updateButtons();

    btnEdit.addEventListener('click', function(e){
        e.preventDefault();
        const href = btnEdit.dataset.href;
        if (!href || href === '#') return;
        fetchAndShow(href);
    });

    btnView.addEventListener('click', function(e){
        e.preventDefault();
        const href = btnView.dataset.href;
        if (!href || href === '#') return;
        fetchAndShow(href);
    });

    btnDelete.addEventListener('click', async function(e){
        e.preventDefault();
        const id = sel.value;
        if (!id) return;
        if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        const resp = await fetch('products_action.php', { method: 'POST', body: fd, credentials: 'same-origin' });
        if (resp.redirected) {
            window.location.href = resp.url;
            return;
        }
        const txt = await resp.text();
        // reload to reflect deletion
        window.location.reload();
    });
});
</script>
