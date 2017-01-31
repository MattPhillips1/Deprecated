package mitcop.eventfinder;

import java.util.Vector;

/**
 * Created by matthew on 12/07/16.
 */
public class Club {
    String name, address, url, about;
    Vector<Event> events;

    public Club(String name, String about, String address, String url, Vector<Event> events){
        this.name = name;
        this.address = address;
        this.url = url;
        this.about = about;
        this.events = events;
    }

    public String getAddress() {
        return this.address;
    }

    public String getUrl() {
        return this.url;
    }

    public String getName() {
        return this.name;
    }

    public Vector<Event> getEvents() {
        return this.events;
    }

    public void addEvent(Event toAdd){
        this.events.add(toAdd);
    }
}
