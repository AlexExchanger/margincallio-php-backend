--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: account; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE account (
    id integer NOT NULL,
    "userId" integer,
    currency character varying(6) NOT NULL,
    status character varying(20) NOT NULL,
    guid character(36) NOT NULL,
    type character varying(80) NOT NULL,
    "creditLimit" numeric(18,8),
    "createdAt" integer NOT NULL,
    "publicId" character varying(510) DEFAULT NULL::character varying,
    gateway character varying(255),
    "tickerId" integer,
    balance numeric(24,15) DEFAULT 0 NOT NULL
);


ALTER TABLE account OWNER TO admin;

--
-- Name: account_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE account_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE account_id_seq OWNER TO admin;

--
-- Name: account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE account_id_seq OWNED BY account.id;


--
-- Name: alarm_code_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE alarm_code_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE alarm_code_seq OWNER TO admin;

--
-- Name: alarm_code; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE alarm_code (
    id bigint DEFAULT nextval('alarm_code_seq'::regclass) NOT NULL,
    "userId" integer,
    code character varying(250)
);


ALTER TABLE alarm_code OWNER TO admin;

--
-- Name: candles_15m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_15m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_15m OWNER TO admin;

--
-- Name: candles_1h; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_1h (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_1h OWNER TO admin;

--
-- Name: candles_1m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_1m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_1m OWNER TO admin;

--
-- Name: candles_5m; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE candles_5m (
    id bigint NOT NULL,
    open numeric(20,15),
    close numeric(20,15),
    high numeric(20,15),
    low numeric(20,15),
    volume bigint,
    "timestamp" bigint,
    currency character varying(6)
);


ALTER TABLE candles_5m OWNER TO admin;

--
-- Name: coin_address_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE coin_address_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE coin_address_id_seq OWNER TO admin;

--
-- Name: coin_address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE coin_address (
    id integer DEFAULT nextval('coin_address_id_seq'::regclass) NOT NULL,
    address character varying(80) NOT NULL,
    "accountId" integer NOT NULL,
    "createdAt" integer NOT NULL,
    used boolean DEFAULT false,
    approve smallint DEFAULT 0,
    amount numeric(23,15),
    "transactionId" bigint,
    "lastTx" character varying(250) DEFAULT NULL::character varying
);


ALTER TABLE coin_address OWNER TO admin;

--
-- Name: deal; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE deal (
    id bigint NOT NULL,
    size numeric(20,15) NOT NULL,
    price numeric(20,15) NOT NULL,
    "orderBuyId" bigint NOT NULL,
    "orderSellId" bigint NOT NULL,
    "createdAt" bigint NOT NULL,
    "userBuyId" bigint NOT NULL,
    "userSellId" bigint NOT NULL,
    "buyerFee" numeric(20,15) NOT NULL,
    "sellerFee" numeric(20,15) NOT NULL,
    side boolean NOT NULL,
    currency character varying(6)
);


ALTER TABLE deal OWNER TO admin;

--
-- Name: file; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE file (
    id integer NOT NULL,
    uid character(32) NOT NULL,
    "fileName" character varying(510) NOT NULL,
    "fileSize" integer NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer,
    "mimeType" character varying(510),
    "entityType" character varying(510) DEFAULT NULL::character varying,
    "entityId" integer
);


ALTER TABLE file OWNER TO admin;

--
-- Name: file_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE file_id_seq OWNER TO admin;

--
-- Name: file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE file_id_seq OWNED BY file.id;


--
-- Name: gateway_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE gateway_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE gateway_id_seq OWNER TO admin;

--
-- Name: gateway; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE gateway (
    id bigint DEFAULT nextval('gateway_id_seq'::regclass) NOT NULL,
    name character varying(250),
    currency character varying(5) NOT NULL,
    class character varying(250) NOT NULL,
    type character varying(250),
    payment text
);


ALTER TABLE gateway OWNER TO admin;

--
-- Name: news; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE news (
    id integer NOT NULL,
    title character varying(510) NOT NULL,
    category character varying(510) NOT NULL,
    content text NOT NULL,
    preview text NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer NOT NULL,
    "updatedAt" integer,
    "updatedBy" integer,
    "isActive" integer NOT NULL,
    number character varying(510) NOT NULL,
    "releaseDate" integer
);


ALTER TABLE news OWNER TO admin;

--
-- Name: news_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE news_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE news_id_seq OWNER TO admin;

--
-- Name: news_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE news_id_seq OWNED BY news.id;


--
-- Name: order; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE "order" (
    id bigint NOT NULL,
    "userId" bigint NOT NULL,
    size numeric(20,15) NOT NULL,
    price numeric(20,15),
    "createdAt" bigint NOT NULL,
    "updatedAt" bigint,
    status character varying(40) NOT NULL,
    type character varying(20) NOT NULL,
    side boolean NOT NULL,
    "offset" numeric(20,15),
    currency character varying(6),
    "actualSize" numeric(20,15)
);


ALTER TABLE "order" OWNER TO admin;

--
-- Name: stat; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE stat (
    id integer NOT NULL,
    indicator character varying(510) DEFAULT NULL::character varying,
    "timestamp" integer,
    value numeric(24,15) DEFAULT NULL::numeric
);


ALTER TABLE stat OWNER TO admin;

--
-- Name: stat_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE stat_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE stat_id_seq OWNER TO admin;

--
-- Name: stat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE stat_id_seq OWNED BY stat.id;


--
-- Name: system_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE system_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE system_id_seq OWNER TO admin;

--
-- Name: system; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE system (
    id integer DEFAULT nextval('system_id_seq'::regclass) NOT NULL,
    name character varying(255),
    value text
);


ALTER TABLE system OWNER TO admin;

--
-- Name: ticket; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE ticket (
    id integer NOT NULL,
    title character varying(510) NOT NULL,
    "createdBy" integer NOT NULL,
    "createdAt" integer NOT NULL,
    status character varying(30) NOT NULL,
    department character varying(40) NOT NULL,
    "updatedAt" integer,
    "updatedBy" integer,
    "messageCount" integer DEFAULT 0 NOT NULL,
    "userId" integer,
    importance character varying(250)
);


ALTER TABLE ticket OWNER TO admin;

--
-- Name: ticket_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE ticket_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE ticket_id_seq OWNER TO admin;

--
-- Name: ticket_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE ticket_id_seq OWNED BY ticket.id;


--
-- Name: ticket_message; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE ticket_message (
    id integer NOT NULL,
    "createdAt" integer NOT NULL,
    "createdBy" integer NOT NULL,
    "ticketId" integer NOT NULL,
    text text NOT NULL,
    files text
);


ALTER TABLE ticket_message OWNER TO admin;

--
-- Name: ticket_message_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE ticket_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE ticket_message_id_seq OWNER TO admin;

--
-- Name: ticket_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE ticket_message_id_seq OWNED BY ticket_message.id;


--
-- Name: transaction; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE transaction (
    id integer NOT NULL,
    account_from integer NOT NULL,
    amount numeric(23,15) NOT NULL,
    "createdAt" integer NOT NULL,
    hash character(16),
    currency character varying(5),
    account_to integer NOT NULL,
    user_from integer NOT NULL,
    user_to integer NOT NULL,
    side boolean DEFAULT false
);


ALTER TABLE transaction OWNER TO admin;

--
-- Name: transaction_external_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE transaction_external_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE transaction_external_id_seq OWNER TO admin;

--
-- Name: transaction_external; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE transaction_external (
    id integer DEFAULT nextval('transaction_external_id_seq'::regclass) NOT NULL,
    "accountId" integer,
    amount numeric(23,15),
    "createdAt" integer,
    hash character(16),
    currency character varying(5),
    "gatewayId" integer,
    type boolean,
    "verifyStatus" character varying(250),
    "verifiedBy" integer,
    details text
);


ALTER TABLE transaction_external OWNER TO admin;

--
-- Name: transaction_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE transaction_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE transaction_id_seq OWNER TO admin;

--
-- Name: transaction_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE transaction_id_seq OWNED BY transaction.id;


--
-- Name: user; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE "user" (
    id integer NOT NULL,
    password character varying(128),
    email character varying(510) NOT NULL,
    "lastLoginAt" integer,
    "emailVerification" character varying(128),
    "inviteCode" character varying(128),
    "createdAt" timestamp(6) with time zone DEFAULT now(),
    blocked boolean DEFAULT false,
    type character varying(30),
    "verifiedBy" integer,
    "verifiedData" character varying(30),
    "verifiedStatus" character varying(30),
    "verifiedReason" text,
    "twoFA" boolean DEFAULT false,
    "verifiedAt" integer,
    "referalPay" integer,
    "parentId" integer,
    "referalCode" character varying(100)
);


ALTER TABLE "user" OWNER TO admin;

--
-- Name: user_early_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_early_seq
    START WITH 2
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_early_seq OWNER TO admin;

--
-- Name: user_early; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_early (
    id bigint DEFAULT nextval('user_early_seq'::regclass) NOT NULL,
    email character varying(255),
    ip character varying(30)
);


ALTER TABLE user_early OWNER TO admin;

--
-- Name: user_external_address_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_external_address_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_external_address_id_seq OWNER TO admin;

--
-- Name: user_external_address; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_external_address (
    id integer DEFAULT nextval('user_external_address_id_seq'::regclass) NOT NULL,
    "userId" integer,
    currency character varying(5),
    address text,
    "accountId" integer
);


ALTER TABLE user_external_address OWNER TO admin;

--
-- Name: user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_id_seq OWNER TO admin;

--
-- Name: user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_id_seq OWNED BY "user".id;


--
-- Name: user_invite; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_invite (
    id character varying(128) NOT NULL,
    activated boolean DEFAULT false NOT NULL,
    email character varying(510) NOT NULL
);


ALTER TABLE user_invite OWNER TO admin;

--
-- Name: user_log; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_log (
    id integer NOT NULL,
    "userId" integer,
    "createdAt" integer,
    action character varying(40) DEFAULT NULL::character varying,
    data text NOT NULL,
    ip character varying(30) DEFAULT NULL::character varying
);


ALTER TABLE user_log OWNER TO admin;

--
-- Name: user_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_log_id_seq OWNER TO admin;

--
-- Name: user_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE user_log_id_seq OWNED BY user_log.id;


--
-- Name: user_phone; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_phone (
    id character varying(128) NOT NULL,
    phone character varying(30) DEFAULT NULL::character varying
);


ALTER TABLE user_phone OWNER TO admin;

--
-- Name: user_transaction_confurm_id; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE user_transaction_confurm_id
    START WITH 2
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE user_transaction_confurm_id OWNER TO admin;

--
-- Name: user_transaction_confurm; Type: TABLE; Schema: public; Owner: postgres; Tablespace: 
--

CREATE TABLE user_transaction_confurm (
    id bigint DEFAULT nextval('user_transaction_confurm_id'::regclass) NOT NULL,
    code character varying(128),
    "userId" bigint,
    details text,
    used boolean DEFAULT false NOT NULL
);


ALTER TABLE user_transaction_confurm OWNER TO admin;

--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY account ALTER COLUMN id SET DEFAULT nextval('account_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY file ALTER COLUMN id SET DEFAULT nextval('file_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY news ALTER COLUMN id SET DEFAULT nextval('news_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY stat ALTER COLUMN id SET DEFAULT nextval('stat_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY ticket ALTER COLUMN id SET DEFAULT nextval('ticket_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY ticket_message ALTER COLUMN id SET DEFAULT nextval('ticket_message_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY transaction ALTER COLUMN id SET DEFAULT nextval('transaction_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY "user" ALTER COLUMN id SET DEFAULT nextval('user_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY user_log ALTER COLUMN id SET DEFAULT nextval('user_log_id_seq'::regclass);


--
-- Name: account_guid_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_guid_key UNIQUE (guid);


--
-- Name: account_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);


--
-- Name: account_publicId_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY account
    ADD CONSTRAINT "account_publicId_key" UNIQUE ("publicId");


--
-- Name: alarm_code_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY alarm_code
    ADD CONSTRAINT alarm_code_pkey PRIMARY KEY (id);


--
-- Name: candles_15m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_15m
    ADD CONSTRAINT candles_15m_pkey PRIMARY KEY (id);


--
-- Name: candles_1h_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_1h
    ADD CONSTRAINT candles_1h_pkey PRIMARY KEY (id);


--
-- Name: candles_1m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_1m
    ADD CONSTRAINT candles_1m_pkey PRIMARY KEY (id);


--
-- Name: candles_5m_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY candles_5m
    ADD CONSTRAINT candles_5m_pkey PRIMARY KEY (id);


--
-- Name: coin_address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY coin_address
    ADD CONSTRAINT coin_address_pkey PRIMARY KEY (id);


--
-- Name: deal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY deal
    ADD CONSTRAINT deal_pkey PRIMARY KEY (id);


--
-- Name: file_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY file
    ADD CONSTRAINT file_pkey PRIMARY KEY (id);


--
-- Name: file_uid_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY file
    ADD CONSTRAINT file_uid_key UNIQUE (uid);


--
-- Name: gateway_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY gateway
    ADD CONSTRAINT gateway_pkey PRIMARY KEY (id);


--
-- Name: news_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY news
    ADD CONSTRAINT news_pkey PRIMARY KEY (id);


--
-- Name: order_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "order"
    ADD CONSTRAINT order_pkey PRIMARY KEY (id);


--
-- Name: stat_indicator_timestamp_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stat
    ADD CONSTRAINT stat_indicator_timestamp_key UNIQUE (indicator, "timestamp");


--
-- Name: stat_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY stat
    ADD CONSTRAINT stat_pkey PRIMARY KEY (id);


--
-- Name: system_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY system
    ADD CONSTRAINT system_pkey PRIMARY KEY (id);


--
-- Name: ticket_message_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY ticket_message
    ADD CONSTRAINT ticket_message_pkey PRIMARY KEY (id);


--
-- Name: ticket_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY ticket
    ADD CONSTRAINT ticket_pkey PRIMARY KEY (id);


--
-- Name: transaction_external_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transaction_external
    ADD CONSTRAINT transaction_external_pkey PRIMARY KEY (id);


--
-- Name: transaction_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY transaction
    ADD CONSTRAINT transaction_pkey PRIMARY KEY (id);


--
-- Name: user_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "user"
    ADD CONSTRAINT user_email_key UNIQUE (email);


--
-- Name: user_external_address_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_external_address
    ADD CONSTRAINT user_external_address_pkey PRIMARY KEY (id);


--
-- Name: user_invite_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_invite
    ADD CONSTRAINT user_invite_pkey PRIMARY KEY (id);


--
-- Name: user_log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_log
    ADD CONSTRAINT user_log_pkey PRIMARY KEY (id);


--
-- Name: user_phone_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_phone
    ADD CONSTRAINT user_phone_pkey PRIMARY KEY (id);


--
-- Name: user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY "user"
    ADD CONSTRAINT user_pkey PRIMARY KEY (id);


--
-- Name: user_transaction_confurm_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres; Tablespace: 
--

ALTER TABLE ONLY user_transaction_confurm
    ADD CONSTRAINT user_transaction_confurm_pkey PRIMARY KEY (id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

