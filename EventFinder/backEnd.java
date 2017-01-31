package mitcop.eventfinder;

import android.content.Context;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.JsonReader;
import android.util.JsonToken;
import android.util.Log;

import com.facebook.AccessToken;
import com.facebook.FacebookSdk;
import com.facebook.GraphRequest;
import com.facebook.GraphResponse;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.text.DateFormat;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.Vector;

/**
 * Created by matthew on 12/07/16.
 */

public class backEnd extends AsyncTask{

    private Integer totalLocations;
    private OnTaskCompleted listener;
    HashMap<String, String[]> locationClubs;
    Integer clubsDone = 0;
    Vector<String> facebookClubIDs;
    String[] SydneyClubIDs = {
            "1696610790555829", // Plan B Small Club
                "86488791246"/*, // Chinese Laundry
                "108976805908366", // El Topo
                "123972314325877", // World Bar
                "191858490883324", // Pure Platinum
                "117575168862", // ARQ Sydney
                "182983535140192", // Marquee Sydney
                "187740824587577", // The Retro Hotel
                "225012514211970", // The Basement Sydney
                "171866576172523", // Red Room Sydney
                "132254376166", // Stonewall Hotel
                "1472360649665954", // Coogee Pavilion
                "40548052695", // The Sydney Hellfire Club
                //"1524805737733837", // Dollhouse Nightspot (No events?)
                "155388504503142", // The Club
                "5712799989", // Oxford Art Factory
                "15534394334", // Q Bar Sydney
                "307710632689704", // Miss Peaches
                //"138217122890197", // Opera Bar (No events?)
                "111101315578049", // The Australian Heritage Hotel
                "80788214822", // Ivy Sydney
                "32755814744", // Cargo Bar
                "99969144228", // Scubar Down Under
                "348124031519", // The Scary Canary
                "113390428671725", // The Star (casino)
                "1450428438518037", // I Remember House
                "138218532879252", // Masif Saturdays
                "155274227827971", // Brewery Coolroom
                "769899266353689", // Ramblin' Rascal Tavern
                "516108121763334", // Papa Gede's Bar
                "433644806674089", // Bulletin Place
                "173391529352592", // Grandma's
                "1596900300584979", // Bar Brose
                "344288612336544", // Monopole
                "156761374416158", // Rockpool Bar & Grill
                "140311622701238", // Viva'z Restaurant & Nightclub
                "809880165755979", // The Valley Bondi
                "131900416858113", // The Sheaf
                "790961797653539"*/ // The Gretz

    };

    backEnd(OnTaskCompleted listener, Vector<String> locationsChosen){
        this.listener = listener;
        locationClubs = new HashMap<>();
        for (String location : locationsChosen){
            switch (location){
                case "Sydney": locationClubs.put(location, SydneyClubIDs);
                    break;
                default:
                    break;
            }


        }
        //this.locationsChosen = locationsChosen;
        this.totalLocations = locationsChosen.size();
    }

    public void downloadFacebook(Context context, Vector<String> locationsChosen){
        setFacebookClubIDs();
        int totalClubs = facebookClubIDs.size();
        String ids = "";
        for (String club : facebookClubIDs) {
            ids += club + ",";
            //getFacebookData(club, totalClubs, context);
        }
        if (ids.length() > 0){
            ids = ids.substring(0, ids.length()-1);
        }
        System.out.println(ids);

        for (String location : locationsChosen) {
            getFacebookData(ids, totalClubs, context, location);
        }

    }

    private void setFacebookClubIDs(){
        facebookClubIDs = new Vector<String>();
        facebookClubIDs.addAll(Arrays.asList(SydneyClubIDs));
    }

    private void getFacebookData(final String clubID, final int totalClubs, final Context context, final String location){

        GraphRequest request = GraphRequest.newGraphPathRequest(AccessToken.getCurrentAccessToken(), "/",
                new GraphRequest.Callback() {
                    @Override
                    public void onCompleted(GraphResponse response) {
                        // Handle the results and data etc
                        if (response.getError() != null){
                            Log.d("Error", response.getError().getErrorMessage());
                            Log.d("DownloadError", "At least the request for worked");
                        }
                        if (response.getRawResponse() != null) {
                            String fileName = location + ".json";
                            FileOutputStream outputStream;
                            JSONObject downloadResults = response.getJSONObject();

                            String results = downloadResults.toString();
                            ++clubsDone;
                            try {
                                context.deleteFile(fileName);
                                outputStream = context.openFileOutput(fileName, Context.MODE_PRIVATE);
                                outputStream.write(results.getBytes());
                                outputStream.close();

                            } catch (FileNotFoundException e) {
                                Log.d("OpenFileError", "Failed to OPEN " + fileName);
                            } catch (IOException e) {
                                Log.d("WriteFileError", "Failed to WRITE " + fileName);
                            }
                            if (clubsDone == totalLocations) {
                                Log.d("FilesWritten", "All Files are written");
                                onPostExecute(null);
                                // Finished all download tasks
                                // Send a signal to stop the downloading indicator etc.
                            }
                        }
                    }
                });
        Bundle parameters = new Bundle();
        parameters.putString("ids", clubID);
        parameters.putString("fields", "name,about,single_line_address,website,events{name,description,start_time}");
        request.setParameters(parameters);
        request.executeAsync();


    }

    public Vector<Club> getClubObjects(Context context) throws IOException {
        Vector clubs = new Vector<Club>();
        String name, address, url, about;

        Vector<Event> eventsAtClub = new Vector<Event>();

        name = "";
        address = "";
        url = "";
        about = "";
        System.out.println("Finding clubs");
        //Will be inside a for loop for each included location

        for (String location : locationClubs.keySet()) {
            try {
                JSONObject currentClubFile = fileAsJSON(context, location);

                for (String club : locationClubs.get(location)) {
                    JSONObject currentClub = currentClubFile.getJSONObject(club);
                    if (currentClub.has("name")) {
                        name = currentClub.getString("name");
                    }
                    if (currentClub.has("single_line_address")) {
                        address = currentClub.getString("single_line_address");
                    }
                    if (currentClub.has("about")) {
                        about = currentClub.getString("about");
                    }
                    if (currentClub.has("website")) {
                        url = currentClub.getString("website");
                    }
                    if (currentClub.has("events")) {
                        JSONObject events = currentClub.getJSONObject("events");
                        eventsAtClub = getEvents(events);
                    }
                    Club toAdd = new Club(name, about, address, url, eventsAtClub);
                    clubs.add(toAdd);


                }
            } catch (JSONException e) {
                Log.d("JSONException", "Could not convert to JSONObject");
            }
        }
        return clubs;

    }

    public JSONObject fileAsJSON(Context context, String location) throws JSONException {
        String fileData = "";

        try {
            InputStream fin = context.openFileInput(location + ".json");
            InputStreamReader finReader = new InputStreamReader(fin);
            BufferedReader br = new BufferedReader(finReader);
            StringBuilder stringBuilder = new StringBuilder();
            try {
                while ((fileData = br.readLine()) != null) {
                    stringBuilder.append(fileData);
                }
                fin.close();
                fileData = stringBuilder.toString();
            } catch (IOException e) {
                Log.d("readContents", location + ".json data cannot be read");
            }
        } catch (FileNotFoundException e) {
            Log.d("readFile", location + ".json not found for read");
        }

        return new JSONObject(fileData);
    }

    private Vector<Event> getEvents(JSONObject eventsObject){
        Vector<Event> events = new Vector<Event>();
        try {
            if (eventsObject.has("data")) {
                JSONArray eventArray = eventsObject.getJSONArray("data");
                int i;
                for (i = 0; i < eventArray.length(); ++i) {
                    JSONObject nextObject = eventArray.getJSONObject(i);
                    if (!hasEventPassed(nextObject.optString("start_time"))) {
                        events.add(makeSingleEvent(nextObject));
                    }
                }
            }
        } catch (JSONException e){
            Log.d("ObjectToArray", "Could noto convert JSONObject to JSONArray");
        }
        return events;
    }

    private Event makeSingleEvent(JSONObject eventDetails) throws JSONException{
        return new Event(eventDetails.optString("start_time"), eventDetails.optString("name"), eventDetails.optString("description"));
    }

    public boolean hasEventPassed(String date){
        if (date == null){
            return false;
        }
        Date today = new Date();
        DateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
        try {
            Date toCheck = dateFormat.parse(date);
            return toCheck.before(today);
        }catch (ParseException e){
            Log.d("parseException", "Failed to parse " + date);
        }

        return false;
    }

    @Override
    protected Object doInBackground(Object[] objects) {
        return null;
    }

    @Override
    protected void onPostExecute(Object o) {
        super.onPostExecute(o);
        listener.onTaskCompleted(this);
    }

    /*private void getFacebookData(final String clubID, final int totalClubs, final Context context){

        GraphRequest request = GraphRequest.newGraphPathRequest(AccessToken.getCurrentAccessToken(), "/" + clubID,
                new GraphRequest.Callback() {
                    @Override
                    public void onCompleted(GraphResponse response) {
                        // Handle the results and data etc
                        if (response.getError() != null){
                            Log.d("DownloadError", "At least the request for worked");
                        }
                        if (response.getRawResponse() != null) {
                            String fileName = clubID + ".json";
                            FileOutputStream outputStream;
                            JSONObject downloadResults = response.getJSONObject();

                            String results = downloadResults.toString();
                            ++clubsDone;
                            try {
                                context.deleteFile(fileName);
                                outputStream = context.openFileOutput(fileName, Context.MODE_PRIVATE);
                                outputStream.write(results.getBytes());
                                outputStream.close();

                            } catch (FileNotFoundException e) {
                                Log.d("OpenFileError", "Failed to OPEN " + fileName);
                            } catch (IOException e) {
                                Log.d("WriteFileError", "Failed to WRITE " + fileName);
                            }
                            if (clubsDone == totalClubs) {
                                Log.d("FilesWritten", "All Files are written");
                                onPostExecute(null);
                                // Finished all download tasks
                                // Send a signal to stop the downloading indicator etc.
                            }
                        }
                    }
                });
        Bundle parameters = new Bundle();
        parameters.putString("fields", "name,about,single_line_address,website,events{name,description,start_time}");
        request.setParameters(parameters);
        request.executeAsync();


    }


    public Vector<Club> getClubObjects(Context context) throws IOException {
        Vector clubs = new Vector<Club>();
        String name, address, url, about;

        Vector<Event> eventsAtClub = new Vector<Event>();

        name = "";
        address = "";
        url = "";
        about = "";
        System.out.println("Finding clubs");

        for (String club : facebookClubIDs){
            try {
                JSONObject currentClubFile = fileAsJSON(context, club);
                if (currentClubFile.has("name")) {
                    name = currentClubFile.getString("name");
                }
                if (currentClubFile.has("single_line_address")) {
                    address = currentClubFile.getString("single_line_address");
                }
                if (currentClubFile.has("about")) {
                    about = currentClubFile.getString("about");
                }
                if (currentClubFile.has("website")){
                    url = currentClubFile.getString("website");
                }
                if (currentClubFile.has("events")) {
                    JSONObject events = currentClubFile.getJSONObject("events");
                    eventsAtClub = getEvents(events);
                }
                Club toAdd = new Club(name, about, address, url, eventsAtClub);
                clubs.add(toAdd);


            } catch(JSONException e){
                Log.d("JSONException", "Could not convert to JSONObject");
            }
        }
        return clubs;
    }

    public JSONObject fileAsJSON(Context context, String club) throws JSONException{
        String fileData = "";

        try {
            InputStream fin = context.openFileInput(club + ".json");
            InputStreamReader finReader = new InputStreamReader(fin);
            BufferedReader br = new BufferedReader(finReader);
            StringBuilder stringBuilder = new StringBuilder();
            try {
                while ( (fileData = br.readLine()) != null ){
                    stringBuilder.append(fileData);
                }
                fin.close();
                fileData = stringBuilder.toString();
            } catch (IOException e){
                Log.d("readContents", club + ".json data cannot be read");
            }
        } catch (FileNotFoundException e){
            Log.d("readFile", club + ".json not found for read");
        }

        return new JSONObject(fileData);
    }
    */


}
