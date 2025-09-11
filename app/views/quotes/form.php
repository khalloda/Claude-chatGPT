<?php
use function App\Core\base_url;
use function App\Core\csrf_field;
?>
<section>
  <h2>New Quote</h2>

  <form method="post" action="<?= base_url('/quotes') ?>" style="display:grid;gap:12px;">
    <?= csrf_field() ?>

    <div>
      <strong>Quote No:</strong>
      <?= htmlspecialchars($quote_no ?? '(assigned on save)', ENT_QUOTES, 'UTF-8') ?>
      <?php if (!empty($next_hint)): ?>
        <span style="color:#6b7280;margin-left:8px;">Next likely: <?= htmlspecialchars($next_hint, ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
    </div>

    <label><div>Customer</div>
      <select name="customer_id" required style="padding:10px;border:1px solid #ddd;border-radius:8px;">
        <option value="">— select —</option>
        <?php foreach ($customers as $c): ?>
          <option value="<?= (int)$c['id'] ?>">
            <?= htmlspecialchars($c['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
      <label>
        <div>Tax %</div>
        <input id="tax_rate" type="number" step="0.01" name="tax_rate" value="<?= isset($tax_rate)?htmlspecialchars((string)$tax_rate,ENT_QUOTES,'UTF-8'):'0' ?>"
               style="padding:10px;border:1px solid #ddd;border-radius:8px;">
      </label>
      <label>
        <div>Expires at</div>
        <input type="date" name="expires_at" style="padding:10px;border:1px solid #ddd;border-radius:8px;">
      </label>
    </div>

    <h3>Items</h3>
    <table style="width:100%;border-collapse:collapse;">
      <thead>
        <tr>
          <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Product</th>
          <th style="text-align:left;border-bottom:1px solid #eee;padding:8px;">Warehouse</th>
          <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Available</th>
          <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Qty</th>
          <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Unit Price</th>
          <th style="text-align:right;border-bottom:1px solid #eee;padding:8px;">Line Total</th>
        </tr>
      </thead>
      <tbody id="rows">
        <?php for ($i=0; $i<($item_rows ?? 3); $i++): ?>
          <tr>
            <td style="padding:6px;">
              <select name="product_id[]" class="js-prod" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;">
                <option value="">— select —</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>"
                          data-price="<?= htmlspecialchars((string)($p['price'] ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($p['code'] ?? '', ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding:6px;">
              <select name="warehouse_id[]" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;">
                <option value="">— select —</option>
                <?php foreach ($warehouses as $w): ?>
                  <option value="<?= (int)$w['id'] ?>"><?= htmlspecialchars($w['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding:6px;text-align:right;">
              <input type="text" class="available-display" value="-"
                     style="width:110px;padding:8px;border:1px solid #eee;background:#fafafa;border-radius:6px;text-align:right;" readonly>
            </td>
            <td style="padding:6px;text-align:right;">
              <input type="number" min="0" name="qty[]" value="0"
                     style="width:110px;padding:8px;border:1px solid #ddd;border-radius:6px;text-align:right;">
            </td>
            <td style="padding:6px;text-align:right;">
              <input type="number" step="0.01" min="0" name="price[]" value="0.00"
                     style="width:130px;padding:8px;border:1px solid #ddd;border-radius:6px;text-align:right;">
            </td>
            <td style="padding:6px;text-align:right;">
              <input type="text" value="0.00" class="line-total"
                     style="width:130px;padding:8px;border:1px solid #eee;background:#fafafa;border-radius:6px;text-align:right;"
                     readonly>
            </td>
          </tr>
        <?php endfor; ?>
      </tbody>
    </table>

    <div style="display:flex;gap:20px;justify-content:flex-end;margin-top:10px;">
      <div>Subtotal: <strong><span id="subtotal">0.00</span></strong></div>
      <div>Tax: <strong><span id="taxamount">0.00</span></strong></div>
      <div>Total: <strong><span id="grandtotal">0.00</span></strong></div>
    </div>

    <button type="button" id="addrow" style="margin-top:8px;padding:6px 10px;border:1px solid #ddd;border-radius:8px;background:#f9f9fb;cursor:pointer;">+ Add row</button>

    <div style="display:flex;gap:10px;margin-top:12px;">
      <button type="submit" style="padding:10px 14px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer;">Save Quote</button>
      <a href="<?= base_url('/quotes') ?>" style="align-self:center;">Cancel</a>
    </div>
  </form>

  <script>
  (function () {
    const rows = document.getElementById('rows');
    const baseStockUrl = '<?= base_url('/stock/available') ?>';
    const cache = new Map(); // key: pid@wid -> {available, ts}

    // simple visual helpers for the Available cell
    function setAvailLoading(el) {
      if (!el) return;
      el.value = '⏳';
      el.style.backgroundColor = '#fff7e6'; // light amber
      el.style.borderColor = '#facc15';
      el.style.color = '#7a5b00';
    }
    function setAvailValue(el, v) {
      if (!el) return;
      el.value = String(v);
      el.style.backgroundColor = '#fafafa';
      el.style.borderColor = '#eee';
      el.style.color = '#111';
    }
    function setAvailError(el) {
      if (!el) return;
      el.value = 'ERR';
      el.style.backgroundColor = '#fde2e2'; // light red
      el.style.borderColor = '#fca5a5';
      el.style.color = '#7f1d1d';
    }

    function toNum(v){ const n=parseFloat(v); return isNaN(n)?0:n; }

    function recalc() {
      let subtotal = 0;
      rows.querySelectorAll('tr').forEach(function (tr) {
        const q  = toNum(tr.querySelector('input[name="qty[]"]').value);
        const pr = toNum(tr.querySelector('input[name="price[]"]').value);
        const lt = q * pr;
        const ltEl = tr.querySelector('.line-total');
        if (ltEl) ltEl.value = lt.toFixed(2);
        subtotal += lt;
      });
      const tax = toNum(document.getElementById('tax_rate') ? document.getElementById('tax_rate').value : 0);
      const taxAmt = subtotal * (tax/100);
      const gt = subtotal + taxAmt;
      const $ = (id) => document.getElementById(id);
      if ($('subtotal'))   $('subtotal').textContent   = subtotal.toFixed(2);
      if ($('taxamount'))  $('taxamount').textContent  = taxAmt.toFixed(2);
      if ($('grandtotal')) $('grandtotal').textContent = gt.toFixed(2);
    }

    function updateRowHighlight(tr) {
      const qty = toNum(tr.querySelector('input[name="qty[]"]').value);
      const availEl = tr.querySelector('.available-display');
      const available = toNum(availEl && availEl.value || '0');
      if (availEl && !isNaN(available) && qty > available) {
        tr.style.outline = '2px solid #fca5a5'; // red-200
        tr.style.backgroundColor = '#fff7f7';
      } else {
        tr.style.outline = '';
        tr.style.backgroundColor = '';
      }
    }

    async function fetchAvailable(pid, wid) {
      const key = pid + '@' + wid;
      if (cache.has(key)) return cache.get(key);
      const url = baseStockUrl + '?product_id=' + encodeURIComponent(pid) + '&warehouse_id=' + encodeURIComponent(wid) + '&_=' + Date.now();
      try {
        const res = await fetch(url, {headers: {'Accept':'application/json'}, credentials: 'same-origin', cache: 'no-store'});
        if (!res.ok) throw new Error('HTTP '+res.status);
        const ct = (res.headers.get('content-type')||'');
        if (ct.indexOf('application/json') === -1) throw new Error('Non-JSON response');
        const data = await res.json();
        const available = Math.max(0, (data.available ?? 0));
        cache.set(key, available);
        return available;
      } catch (e) {
        return 0; // treat as unknown; UI will show 0 but row highlight and server-side guard still protect
      }
    }

    async function updateAvailableDisplay(tr) {
      const pid = parseInt((tr.querySelector('select[name="product_id[]"]').value)||'0',10);
      const wid = parseInt((tr.querySelector('select[name="warehouse_id[]"]').value)||'0',10);
      const el  = tr.querySelector('.available-display');
      if (!el) return;
      if (!pid || !wid) { setAvailValue(el, '-'); updateRowHighlight(tr); return; }
      setAvailLoading(el);
      try {
        const available = await fetchAvailable(pid, wid);
        setAvailValue(el, available);
      } catch (e) {
        console.error('Available fetch failed', e);
        setAvailError(el);
      }
      updateRowHighlight(tr);
    }

    // Always update price when product changes; set qty=1 if empty/0; zero-out if cleared.
    rows.addEventListener('change', function (e) {
      if (e.target && e.target.name === 'product_id[]') {
        const tr = e.target.closest('tr');
        const priceInput = tr.querySelector('input[name="price[]"]');
        const qtyInput   = tr.querySelector('input[name="qty[]"]');
        const opt = e.target.options[e.target.selectedIndex];
        const p = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
        if (e.target.value) {
          priceInput.value = (isNaN(p) ? 0 : p).toFixed(2);  // overwrite every time
          if (!qtyInput.value || parseFloat(qtyInput.value) <= 0) qtyInput.value = '1';
        } else {
          priceInput.value = '0.00';
          if (parseFloat(qtyInput.value) > 0) qtyInput.value = '0';
        }
        updateAvailableDisplay(tr);
        recalc();
      }
      if (e.target && e.target.name === 'warehouse_id[]') {
        const tr = e.target.closest('tr');
        updateAvailableDisplay(tr);
      }
    });

    // Delegate input events for qty/price to keep totals live
    rows.addEventListener('input', function (e) {
      if (e.target && (e.target.name === 'qty[]' || e.target.name === 'price[]')) {
        recalc();
        if (e.target.name === 'qty[]') {
          const tr = e.target.closest('tr');
          updateRowHighlight(tr);
        }
      }
    });

    // Add row button – clone first row, clear inputs, and keep delegation working
    const addBtn = document.getElementById('addrow');
    if (addBtn) addBtn.addEventListener('click', function () {
      const tr0 = rows.children[0];
      if (!tr0) return;
      const tr = tr0.cloneNode(true);
      tr.querySelector('select[name="product_id[]"]').value = '';
      tr.querySelector('select[name="warehouse_id[]"]').value = '';
      tr.querySelector('input[name="qty[]"]').value = '0';
      tr.querySelector('input[name="price[]"]').value = '0.00';
      tr.querySelector('.line-total').value = '0.00';
      rows.appendChild(tr);
      updateAvailableDisplay(tr);
      recalc();
    });

    // Initial totals
    recalc();
  })();
  </script>
</section>
