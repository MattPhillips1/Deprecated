package mitcop.eventfinder;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.net.Uri;
import android.support.v4.app.Fragment;
import android.support.v4.app.FragmentActivity;
import android.support.v4.app.FragmentManager;
import android.support.v4.app.FragmentTransaction;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.AttributeSet;
import android.util.Log;
import android.view.View;

import com.facebook.AccessToken;
import com.facebook.FacebookSdk;
import com.facebook.Profile;
import com.facebook.ProfileTracker;

import layout.BlankFragment;

public class MainActivity extends AppCompatActivity implements facebookLogin.OnFragmentInteractionListener{

    ProfileTracker mProfileTracker;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        FacebookSdk.sdkInitialize(getApplicationContext());
        mProfileTracker = new ProfileTracker() {
            @Override
            protected void onCurrentProfileChanged(Profile old, Profile newProfile) {

            }
        };

        mProfileTracker.startTracking();
        setContentView(R.layout.activity_main);
        FragmentManager fragmentManager = getSupportFragmentManager();
        FragmentTransaction fragmentTransaction = fragmentManager.beginTransaction();
        //BlankFragment options = new BlankFragment();
        //fragmentTransaction.add(R.id.fragment_container, options);
        //fragmentTransaction.commit();
        if (!isLoggedin()) {
            facebookLogin fragment = new facebookLogin();
            fragmentTransaction.add(R.id.fragment_container, fragment);
            fragmentTransaction.commit();
        } else {
            Intent postLogin = new Intent(getApplicationContext(), postLogMain.class);
            Log.d("Else", "Should load new activity");
            //setContentView(R.layout.activity_post_log_main);
            startActivity(postLogin);
        }



    }
/*
    @Override
    public View onCreateView(String name, Context context, AttributeSet attrs) {
        Log.d("Which", "Is onCreateView first?");
        return super.onCreateView(name, context, attrs);
    }
*/
    @Override
    public void onFragmentInteraction(Uri uri) {

    }


    private boolean isLoggedin(){
        Profile profile = Profile.getCurrentProfile();
        Log.d("Logon", "Checking log on " + String.valueOf(profile != null));
        return profile != null;
    }

    @Override
    protected void onStop() {
        super.onStop();
        mProfileTracker.stopTracking();
    }
}
