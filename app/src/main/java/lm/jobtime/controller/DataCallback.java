package lm.jobtime.controller;

public interface DataCallback<T> {
    void onDataLoaded(T data);
}
