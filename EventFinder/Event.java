package mitcop.eventfinder;

/**
 * Created by matthew on 12/07/16.
 */
public class Event {
    String date, title, description;
    Club host;
    public Event(String date, String title, String description){
        this.date = date;
        this.title = title;
        this.description = description;
    }

    public void setHost(Club host) { this.host = host; }

    public String getDate() {
        return this.date;
    }

    public String getTitle() {
        return this.title;
    }

    public Club getHost() {
        return this.host;
    }
}
