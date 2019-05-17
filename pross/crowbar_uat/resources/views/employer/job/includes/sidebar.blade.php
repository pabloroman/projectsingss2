<div class="col-md-3 left-sidebar">
    <div class="user-profile-image">
        <div class="user-display-details">
            <div class="user-display-image">
                <img src="{{ asset($user['picture']) }}" />
                <div class="fileUpload btn btn-default"><span>Change Photo</span><input type="file" class="upload" /></div>
            </div>
            <div class="user-name-info">
                <a href="javascript:void(0);" title="Edit" class="edit-me"><img src="{{ asset('images/edit-icon.png') }}" /></a>
                <p>{{ sprintf("%s %s",$user['first_name'],$user['last_name']) }}</p>
                <span>{{ $user['email'] }}</span>
            </div>
        </div>
    </div>
    <div class="profile-completion-block">
        <h3>Profile Completion <span>25%</span></h3>
        <div class="completion-bar">
            <span style="width: 25%;"></span>
        </div>
        <ul class="completion-list-group">
            <li class="completed">Account Creation</li>
            <li>General Information</li>
            <li>Setup Profile</li>
            <li>Verify Account</li>
        </ul>
    </div>
</div>
