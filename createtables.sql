/*
`   Created by Brian Van Norman
    4/12/23
*/

create table Song (
    ID int primary key, SongName char(30) not null, 
    Artist char(30)
);

create table File (
    ID int primary key, FileVersion char(15), 
    SongID int not null,

    primary key (SongID),
    foreign key (SongID) references Song(ID)
);

create table Contributor (
    ID int primary key, ContName char(30)
);

create table User (
    ID int primary key, UserName char(30)
);

create table Queue (
    ID int primary key, UserID int not null, 
    FileID int not null, Priority? boolean, 
    Played? boolean,

    primary key (UserID),
    primary key (FileID),
    foreign key (UserID) references User(ID),
    foreign key (FileID) references File(ID)
);

create table Contribution (
    ContributorID int not null, SongID int not null, 
    ContRole char(15),

    primary key (ContributorID), 
    primary key (SongID),
    foreign key (ContributorID) references Contributor(ID),
    foreign key (SongID) references Song(ID)
);