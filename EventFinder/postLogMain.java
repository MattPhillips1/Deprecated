package mitcop.eventfinder;

import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.content.pm.Signature;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Base64;
import android.util.Log;
import android.view.View;
import android.widget.TextView;

import com.facebook.AccessToken;
import com.facebook.AccessTokenTracker;

import java.io.IOException;
import java.security.MessageDigest;
import java.util.Vector;

public class postLogMain extends AppCompatActivity implements OnTaskCompleted {

    private Vector<Club> clubs;
    private AccessTokenTracker mTokenTracker;

    private Vector<String> setLocations(){
        Vector<String> locationsChosen = new Vector<String>();
        locationsChosen.add("Sydney");

        return locationsChosen;
    }
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_post_log_main);
        Vector<String> locationsChosen = setLocations();
        backEnd back = new backEnd(this, locationsChosen);
        // Need to change the one and find out how many locations have been chosen
        mTokenTracker = new AccessTokenTracker() {
            @Override
            protected void onCurrentAccessTokenChanged(AccessToken old, AccessToken newToken) {

            }
        };
        mTokenTracker.startTracking();
        //getFbKeyHash();
        back.downloadFacebook(getApplicationContext(), locationsChosen);

    }

    @Override
    protected void onStop() {
        super.onStop();
        mTokenTracker.stopTracking();
    }



    @Override
    public void onTaskCompleted(backEnd back) {
        try {
            clubs = back.getClubObjects(getApplicationContext());
            String data = "";
            for (Club club : clubs){
                data += club.getName() + "::\n\n";
                Log.d("clubName", club.getName());
                for (Event clubEvent : club.getEvents()){
                    clubEvent.setHost(club);
                    data += clubEvent.getTitle() + "\n";
                    Log.d("eventName", clubEvent.getDate());
                    Log.d("eventTitle", clubEvent.getTitle());
                }
            }
            TextView text = (TextView) findViewById(R.id.jsonText);
            text.setText(data);
        } catch (IOException e){
            Log.d("taskComplete", "Getting club bjects after download failed");
        }
    }
}

/*

    private void getFbKeyHash(){
        PackageInfo info;
        try {

            info = getPackageManager().getPackageInfo(
                    "mitcop.eventfinder", PackageManager.GET_SIGNATURES);

            for (Signature signature : info.signatures) {
                MessageDigest md;
                md = MessageDigest.getInstance("SHA");
                md.update(signature.toByteArray());
                String hashString = new String(Base64.encode(md.digest(), 0));
                System.out.println("App KeyHash : " + hashString);
            }
        } catch (Exception e) {
            Log.e("exception", e.toString());
        }
    }

*/
