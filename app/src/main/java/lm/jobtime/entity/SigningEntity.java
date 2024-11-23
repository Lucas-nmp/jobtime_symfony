package lm.jobtime.entity;

import androidx.annotation.NonNull;
import androidx.room.Entity;
import androidx.room.Index;
import androidx.room.PrimaryKey;

import org.w3c.dom.Text;

@Entity(tableName = "singing", indices = {@Index(value = "id", unique = true)})
public class SigningEntity {

    @PrimaryKey(autoGenerate = true)
    @NonNull
    private Integer id;

    @NonNull
    private String signing;

    @NonNull
    private Boolean entrance;

    // TODO crear dos propiedades una observacines y otra de url para añadir archivos como el justificante

    // TODO tal vez podría añadir un boolean para identificar las entradas manuales que
    // despúes aparezcan en el informe y en el recycler con un asterisco o algo que las identifique

    @NonNull
    public Integer getId() {
        return id;
    }

    public void setId(@NonNull Integer id) {
        this.id = id;
    }

    @NonNull
    public String getSigning() {
        return signing;
    }

    public void setSigning(@NonNull String signing) {
        this.signing = signing;
    }

    @NonNull
    public Boolean getEntrance() {
        return entrance;
    }

    public void setEntrance(@NonNull Boolean entrance) {
        this.entrance = entrance;
    }
}
