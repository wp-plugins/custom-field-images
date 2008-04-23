function confirm_delete() {
	input_box=confirm("Are you sure you want to do this?\nIt cannot be undone.");
	if (input_box == true)
		return true;
	else
		return false;
}