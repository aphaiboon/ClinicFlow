import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>,
) {
    return (
        <img
            {...props}
            src="/images/clinicflow-icon-logo.png"
            alt="ClinicFlow"
        />
    );
}
