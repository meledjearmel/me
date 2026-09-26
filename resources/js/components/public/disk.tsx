export default function Disk({ spinning }: { spinning: boolean }) {
    return (
        <div
            className={`pub-disk${spinning ? ' is-spinning' : ''}`}
            aria-hidden="true"
        >
            <span className="pub-disk__face" />
            <span className="pub-disk__cap pub-disk__cap--1" />
            <span className="pub-disk__cap pub-disk__cap--2" />
            <span className="pub-disk__cap pub-disk__cap--3" />
        </div>
    );
}
