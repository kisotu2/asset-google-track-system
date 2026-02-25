<!-- ADD ICT ASSET -->
<div id="add-asset" class="section">
<h2>Register ICT Asset</h2>
<form method="POST">

<input type="text" name="asset_tag" placeholder="Asset Tag" required>
<input type="text" name="serial_number" placeholder="Serial Number" required>
<input type="text" name="brand" placeholder="Brand">
<input type="text" name="model" placeholder="Model">

<select name="status">
<option value="Active">Active</option>
<option value="Faulty">Faulty</option>
<option value="Retired">Retired</option>
</select>

<label>Purchase Date</label>
<input type="date" name="purchase_date">

<label>Warranty Expiry</label>
<input type="date" name="warranty_expiry">

<button type="submit" name="add_asset">Register Asset</button>

</form>
</div>