CREATE TABLE draft_decisions (
  id bigserial PRIMARY KEY,
  key_val varchar(45) NOT NULL,
  senior_editor_id numeric(11) NOT NULL,
  junior_editor_id numeric(11) NOT NULL,
  article_id numeric(11) NOT NULL,
  decision numeric(11) NOT NULL,
  subject varchar(200) DEFAULT NULL,
  body text,
  note text,
  attatchment text,
  status varchar(45) NOT NULL,
  PRIMARY KEY (id)
) ;
