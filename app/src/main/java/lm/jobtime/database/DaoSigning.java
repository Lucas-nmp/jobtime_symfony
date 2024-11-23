package lm.jobtime.database;

import androidx.room.Dao;
import androidx.room.Insert;
import androidx.room.Query;

import java.util.List;

import lm.jobtime.entity.SigningEntity;

@Dao
public interface DaoSigning {

    @Insert
    public void insert(SigningEntity...signingEntities);

    @Query("SELECT * FROM singing ORDER BY signing DESC")
    List<SigningEntity> getAllSingingsDesc();

    @Query("SELECT * FROM singing")
    List<SigningEntity> getAllSingings();



}
