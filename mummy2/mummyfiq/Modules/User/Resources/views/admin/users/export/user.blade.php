<table>
	<thead>
		<tr>
			<th>First Name</th>
			<th>Last Name</th>
			<th>Email</th>
			<th>Role</th>
			<th>Created Date Time</th>
			<th>Last Login Date Time</th>
		</tr>
	</thead>
	<tbody>
		@if(count($users) > 0)
			@foreach($users as $key => $user)
				<tr>
					<td>{{ ucfirst($user->first_name) }}</td>
					<td>{{ ucfirst($user->last_name) }}</td>
					<td>{{ $user->email }}</td>
					<td>{{ $user->roleName }}</td>
					<td>{{ $user->created_at && $user->created_at != \Carbon\Carbon::create(0, 0, 0, 0) ? \Carbon\Carbon::parse($user->created_at)->format('d/m/Y H:i:s') : null}}</td>
					<td>{{ $user->last_login && $user->last_login != \Carbon\Carbon::create(0, 0, 0, 0) ? \Carbon\Carbon::parse($user->last_login)->format('d/m/Y H:i:s') : null }}</td>
				</tr>
			@endforeach
		@endif
	</tbody>
</table>